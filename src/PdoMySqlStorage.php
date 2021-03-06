<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\Service\Storage;

use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\Grammar\GrammarInterface;
use Tobento\Service\Storage\Grammar\PdoMySqlGrammar;
use Closure;
use PDO;

/**
 * PdoMySqlStorage
 */
class PdoMySqlStorage extends Storage
{
    /**
     * @var int
     */    
    protected int $transactionLevel = 0;
    
    /**
     * Create a new PdoMySqlStorage.
     *
     * @param PDO $pdo
     * @param null|TablesInterface $tables
     */    
    public function __construct(
        protected PDO $pdo,
        null|TablesInterface $tables = null,
    ) {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql')
        {
            throw new StorageException('PdoMySqlStorage only supports mysql driver');
        }
        
        parent::__construct($tables);
    }

    /**
     * Fetches the table items.
     *
     * @param string $table The table name.
     * @return iterable The items fetched.
     */
    public function fetchItems(string $table): iterable
    {
        if (is_null($table = $this->tables()->verifyTable($table))) {
            return [];
        }
        
        return $this->table($table->name())->get()->getIterator();
    }
    
    /**
     * Stores the table items.
     *
     * @param string $table The table name.
     * @param iterable $items The items to store.
     * @return iterable The stored items.
     */
    public function storeItems(string $table, iterable $items): iterable
    {
        if (is_null($table = $this->tables()->verifyTable($table))) {
            return [];
        }
        
        $pdoStatement = $this->pdo->prepare('TRUNCATE `'.$table->name().'`');
        $pdoStatement->execute();
        
        $stored = [];
        
        foreach($items as $item)
        {
            $result = $this->table($table->name())->insert($item);
            $stored[] = $result->item()->all();
        }
        
        return $stored;
    }
    
    /**
     * Get a single item by id.
     *
     * @param int|string $id
     * @return null|ItemInterface
     */
    public function find(int|string $id): null|ItemInterface
    {        
        $primaryKey = $this->tables()->getPrimaryKey($this->getTable());
        
        $primaryKey = $primaryKey === null ? 'id' : $primaryKey;
        
        return $this->where($primaryKey, '=', $id)->first();
    }
    
    /**
     * Get a single item.
     *
     * @return null|ItemInterface
     */
    public function first(): null|ItemInterface
    {
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->select($this->select)
            ->table($this->table ?? '')
            ->joins($this->joins)
            ->wheres($this->wheres)
            ->groups($this->groups)
            ->havings($this->havings)
            ->orders($this->orders)
            ->limit($this->limit)
            ->bindings($this->bindings);
        
        $this->grammar = $grammar;

        if ($this->skipQuery) {
            return null;
        }
        
        $pdoStatement = $this->pdo->prepare($grammar->getStatement());
        $pdoStatement->execute($grammar->getBindings());
        
        $this->clear();
        
        $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        return is_array($data) ? new Item($data) : null;
    }
        
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function get(): ItemsInterface
    {
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->select($this->select)
            ->table($this->table ?? '')
            ->joins($this->joins)
            ->wheres($this->wheres)
            ->groups($this->groups)
            ->havings($this->havings)
            ->orders($this->orders)
            ->limit($this->limit)
            ->index($this->index)
            ->bindings($this->bindings);
        
        
        $fetchMode = PDO::FETCH_ASSOC;
        
        if ($this->index) {
            $fetchMode = PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE;
        }
        
        $this->grammar = $grammar;

        if ($this->skipQuery) {
            return new Items();
        }
        
        $pdoStatement = $this->pdo->prepare($grammar->getStatement());
        $pdoStatement->execute($grammar->getBindings());
        
        $this->clear();
        
        return new Items($pdoStatement->fetchAll($fetchMode));
    }

    /**
     * Get column's value from the items.
     *
     * @param string $column The column name
     * @param null|string $key The column name for the index
     * @return ItemInterface
     */
    public function column(string $column, null|string $key = null): ItemInterface
    {        
        if (is_null($column = $this->tables()->verifyColumn($column))) {
            $this->clear();
            return new Item();
        }
        
        $this->select($column->column());
        $fetchMode = PDO::FETCH_COLUMN;
        
        if (
            !is_null($key)
            && !is_null($key = $this->tables()->verifyColumn($key))
        ) {
            if ($key->column() === $column->column()) {
                $column = $column->withAlias($column->name());
            }
            
            $this->select($key->column(), $column->column());
            $fetchMode = PDO::FETCH_KEY_PAIR;
        }
        
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->select($this->select)
            ->table($this->table ?? '')
            ->joins($this->joins)
            ->wheres($this->wheres)
            ->groups($this->groups)
            ->havings($this->havings)
            ->orders($this->orders)
            ->limit($this->limit)
            ->bindings($this->bindings);
        
        $this->grammar = $grammar;        

        if ($this->skipQuery) {
            return new Item();
        }
                
        $pdoStatement = $this->pdo->prepare($grammar->getStatement());
        $pdoStatement->execute($grammar->getBindings());
        $this->clear();
        return new Item($pdoStatement->fetchAll($fetchMode));
    }

    /**
     * Get the count of the query.
     *
     * @return int
     */    
    public function count(): int
    {
        $this->select = 'count(*) as aggregate';
        $item = $this->first();
        return (int) ($item['aggregate'] ?? 0);
    }

    /**
     * Get the raw statement result.
     *
     * @param string $statement 'SELECT title From products WHERE status = ?'
     * @param array $bindings Any bindings. ['active']
     * @param string $mode The mode such as 'first' or 'all'
     * @return mixed
     */    
    public function selectRaw(string $statement, array $bindings = [], string $mode = 'all'): mixed
    { 
        $pdoStatement = $this->pdo->prepare($statement);
        $pdoStatement->execute($bindings);
        
        if ($mode === 'all') {
            return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }

        return $pdoStatement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Insert item(s).
     *
     * @param array $item The item data
     * @return null|ResultInterface The result on success, otherwise null.
     */    
    public function insert(array $item): null|ResultInterface
    {
        $table = $this->table;
        $grammar = (new PdoMySqlGrammar($this->tables()))->table($table)->insert($item);
        $this->grammar = $grammar;

        if ($this->skipQuery) {
            return null;
        }
        
        $this->clear();
        
        if ($statement = $grammar->getStatement())
        {
            $pdoStatement = $this->pdo->prepare($statement);
            $pdoStatement->execute($grammar->getBindings());
            $item = $grammar->getItem();
            
            if ($primaryKey = $this->tables()->getPrimaryKey($table))
            {
                $item[$primaryKey] = $item[$primaryKey] ?? (int) $this->pdo->lastInsertId();
            }
            
            return new Result(
                action: 'insert',
                item: new Item($item),
                items: new Items(),
            );            
        }
        
        return null;
    }

    /**
     * Update item(s).
     *
     * @param array $item The item data
     * @return null|ResultInterface The result on success, otherwise null.
     */    
    public function update(array $item): null|ResultInterface
    {
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->table($this->table ?? '')
            ->wheres($this->wheres)
            ->orders($this->orders)
            ->limit($this->limit);
        
        $queryGrammar = clone $grammar;
        $queryGrammar->select($this->select);
        
        $grammar->update($item);
        
        $this->grammar = $grammar;
        
        if ($this->skipQuery) {
            return null;
        }
        
        $this->clear();
        
        if ($statement = $grammar->getStatement())
        {
            $pdoStatement = $this->pdo->prepare($statement);
            $pdoStatement->execute($grammar->getBindings());
            
            return new PdoMySqlResult(
                action: 'update',
                item: new Item($grammar->getItem()),
                items: new Items(),
                query: $queryGrammar,
                pdo: $this->pdo,
            );
        }

        return null;
    }

    /**
     * Update or insert item(s).
     *
     * @param array $attributes The attributes to query.
     * @param array $item The item data
     * @return null|ResultInterface The result on success, otherwise null.
     */    
    public function updateOrInsert(array $attributes, array $item): null|ResultInterface
    {
        foreach($attributes as $column => $value) {
            $this->where($column, '=', $value);
        }
        
        // update if entity exists.
        if (!is_null($firstItem = $this->first()?->all()))
        {
            // Set primary key value
            $primaryKey = $this->tables()->getPrimaryKey($this->table);

            if ($primaryKey && array_key_exists($primaryKey, $firstItem))
            {
                return $this->where($primaryKey, '=', $firstItem[$primaryKey])->update($item);
            }

            foreach($attributes as $column => $value)
            {
                $this->where($column, '=', $value);
            }
            
            return $this->update($attributes + $item);
        }
        
        // create
        return $this->insert($attributes + $item);
    }
    
    /**
     * Delete item(s).
     *
     * @return null|ResultInterface The result on success, otherwise null.
     */    
    public function delete(): null|ResultInterface
    {
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->table($this->table ?? '')
            ->wheres($this->wheres)
            ->orders($this->orders)
            ->limit($this->limit);
        
        $queryGrammar = clone $grammar;
        $queryGrammar->select($this->select);
        
        $grammar->delete();
        
        $this->grammar = $grammar;
        
        if ($this->skipQuery) {
            return null;
        }
        
        $this->clear();
        $deletedData = [];
        
        if ($statement = $queryGrammar->getStatement())
        {            
            $pdoStatement = $this->pdo->prepare($statement);
            $pdoStatement->execute($queryGrammar->getBindings());    
            $deletedData = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($statement = $grammar->getStatement())
        {    
            $pdoStatement = $this->pdo->prepare($statement);
            $pdoStatement->execute($grammar->getBindings());
            
            return new Result(
                action: 'delete',
                item: new Item(),
                items: new Items($deletedData),
            );
        }
        
        return null;
    }
    
    /**
     * Begin a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function begin(): bool
    {
        if($this->transactionLevel === 0 || !$this->supportsNestedTransactions()) {
            $this->transactionLevel++;
            return $this->pdo->beginTransaction();
        }        
        
        $this->pdo->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
        
        $this->transactionLevel++;
        
        return true;
    }
    
    /**
     * Commit a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function commit(): bool
    {
        $this->transactionLevel--;
        
        if($this->transactionLevel === 0 || !$this->supportsNestedTransactions()) {
            return $this->pdo->commit();
        }
        
        $this->pdo->exec("RELEASE SAVEPOINT LEVEL{$this->transactionLevel}");
        
        return true;
    }

    /**
     * Rollback a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function rollback(): bool
    {
        $this->transactionLevel--;

        if($this->transactionLevel === 0 || !$this->supportsNestedTransactions()) {
            return $this->pdo->rollBack();
        }
        
        $this->pdo->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionLevel}");
        
        return true;
    }
    
    /**
     * Returns true if supporting nested transactions, otherwise false.
     *
     * @return bool
     */
    public function supportsNestedTransactions(): bool
    {
        return true;
    }    

    /**
     * Get the query.
     *
     * param null|Closure $callback
     * @return array [statement, bindings[]]
     */    
    public function getQuery(null|Closure $callback = null): array
    {
        if (!is_null($callback)) {
        
            $this->skipQuery = true;
            call_user_func($callback, $this);
            $this->skipQuery = false;
            
            $statement = $this->grammar->getStatement();
            $bindings = $this->grammar->getBindings();
            $this->clear();
            
            return [$statement, $bindings];    
        }
        
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->select($this->select)
            ->table($this->table ?? '')
            ->joins($this->joins)
            ->wheres($this->wheres)
            ->groups($this->groups)
            ->havings($this->havings)
            ->orders($this->orders)
            ->limit($this->limit)
            ->index($this->index)
            ->bindings($this->bindings);
        
        $statement = $grammar->getStatement();
        $bindings = $grammar->getBindings();
        
        return [$statement, $bindings];
    }
}