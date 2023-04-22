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
use PDOStatement;
use Exception;
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
     * @var int
     */
    protected null|array $chunk = null;
    
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
     * Returns the pdo.
     *
     * @return PDO
     */
    public function pdo(): PDO
    {
        return $this->pdo;
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
        
        $this->execute(
            statement: 'TRUNCATE `'.$table->name().'`',
            bindings: []
        );

        return $this->table($table->name())->insertItems($items);
    }
    
    /**
     * Deletes the specified table.
     *
     * @param string $table The table name.
     * @return void
     */
    public function deleteTable(string $table): void
    {
        if (is_null($table = $this->tables()->verifyTable($table))) {
            return;
        }
        
        $this->execute(
            statement: 'DROP TABLE IF EXISTS `'.$table->name().'`',
            bindings: []
        );
        
        $this->tables()->removeTable($table->name());
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
        
        $item = $this->where($primaryKey, '=', $id)->first();
        
        if ($item && $item instanceof Item) {
            return $item->withAction('find');
        }
        
        return $item;
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
        
        $pdoStatement = $this->execute(
            statement: $grammar->getStatement(),
            bindings: $grammar->getBindings()
        );
        
        $this->clear();
        
        $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        return is_array($data) ? new Item($data, action: 'first') : null;
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
            return new Items(action: 'get');
        }
        
        $pdoStatement = $this->execute(
            statement: $grammar->getStatement(),
            bindings: $grammar->getBindings()
        );
        
        $this->clear();

        return new Items($pdoStatement->fetchAll($fetchMode), action: 'get');
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
            return new Item(action: 'column');
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
            return new Item(action: 'column');
        }

        $pdoStatement = $this->execute(
            statement: $grammar->getStatement(),
            bindings: $grammar->getBindings()
        );        

        $this->clear();
        
        return new Item($pdoStatement->fetchAll($fetchMode), action: 'column');
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
        $pdoStatement = $this->execute(
            statement: $statement,
            bindings: $bindings
        );
        
        if ($mode === 'all') {
            return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }

        return $pdoStatement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Insert an item.
     *
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return ItemInterface The item inserted.
     */    
    public function insert(array $item, null|array $return = []): ItemInterface
    {
        $table = $this->table;
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->table($table)
            ->insert($item, $return);
        
        $this->grammar = $grammar;

        if ($this->skipQuery) {
            return new Item(action: 'insert');
        }
        
        $this->clear();
        
        $statement = $grammar->getStatement();
        
        $this->execute(
            statement: $statement,
            bindings: $grammar->getBindings()
        );
        
        $item = $grammar->getItem();
        $primaryKey = $this->tables()->getPrimaryKey($table);

        if (
            $primaryKey
            && is_array($return)
            && (in_array($primaryKey, $return) || empty($return))
        ) {
            $item[$primaryKey] = $item[$primaryKey] ?? (int) $this->pdo->lastInsertId();
        }
        
        // might be supported in future version.
        /*if (str_contains($statement, 'RETURNING')) {
            return new Item($pdoStatement->fetch(PDO::FETCH_ASSOC), action: 'insert');
        }*/

        return new Item($item, action: 'insert');
    }
    
    /**
     * Insert items.
     *
     * @param iterable $items
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The items inserted.
     */
    public function insertItems(iterable $items, null|array $return = []): ItemsInterface
    {
        $table = $this->table;
        
        if ($this->skipQuery) {
            $this->grammar = (new PdoMySqlGrammar($this->tables()))
                ->table($table)
                ->insertItems($items, $return);
            return new Items(action: 'insertItems');
        }

        $this->clear();
        
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->table($table)
            ->insertItems($items, $return);

        if ($grammar->getStatement())
        {
            $pdoStatement = $this->execute(
                statement: $grammar->getStatement(),
                bindings: $grammar->getBindings()
            );
            
            // might be supported in future version.
            /*if (str_contains($statement, 'RETURNING')) {
                return new Items($pdoStatement->fetchAll(PDO::FETCH_ASSOC), action: 'insertItems');
            }*/
            
            return new Items(action: 'insertItems', itemsCount: $pdoStatement->rowCount());
        }
        
        return new Items(action: 'insertItems');
    }

    /**
     * Update item(s).
     *
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The updated items.
     */
    public function update(array $item, null|array $return = []): ItemsInterface
    {
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->table($this->table ?? '')
            ->wheres($this->wheres)
            ->orders($this->orders)
            ->limit($this->limit)
            ->update($item, $return);
        
        $this->grammar = $grammar;
        
        if ($this->skipQuery) {
            return new Items(action: 'update');
        }
        
        $this->clear();
        
        if ($statement = $grammar->getStatement())
        {
            $pdoStatement = $this->execute(
                statement: $statement,
                bindings: $grammar->getBindings()
            );

            // Note: returning statements are not supported!
            return new Items(action: 'update', itemsCount: $pdoStatement->rowCount());
        }

        return new Items(action: 'update');
    }

    /**
     * Update or insert item(s).
     *
     * @param array $attributes The attributes to query.
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return ItemInterface|ItemsInterface
     */
    public function updateOrInsert(
        array $attributes,
        array $item,
        null|array $return = []
    ): ItemInterface|ItemsInterface {
        
        foreach($attributes as $column => $value) {
            $this->where($column, '=', $value);
        }
        
        // update if item exists.
        if (!is_null($firstItem = $this->first()?->all()))
        {
            // Set primary key value
            $primaryKey = $this->tables()->getPrimaryKey($this->table);

            if ($primaryKey && array_key_exists($primaryKey, $firstItem))
            {
                return $this->where($primaryKey, '=', $firstItem[$primaryKey])->update($item, $return);
            }

            foreach($attributes as $column => $value)
            {
                $this->where($column, '=', $value);
            }
            
            return $this->update($attributes + $item, $return);
        }
        
        return $this->insert($attributes + $item, $return);
    }
    
    /**
     * Delete item(s).
     *
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The deleted items.
     */
    public function delete(null|array $return = []): ItemsInterface
    {
        $grammar = (new PdoMySqlGrammar($this->tables()))
            ->table($this->table ?? '')
            ->wheres($this->wheres)
            ->orders($this->orders)
            ->limit($this->limit)
            ->delete($return);

        $this->grammar = $grammar;
        
        if ($this->skipQuery) {
            return new Items(action: 'delete');
        }
        
        $this->clear();
        
        if ($statement = $grammar->getStatement())
        {
            $pdoStatement = $this->execute(
                statement: $statement,
                bindings: $grammar->getBindings()
            );
            
            // might be supported in future version.
            /*if (str_contains($statement, 'RETURNING')) {
                return new Items($pdoStatement->fetchAll(PDO::FETCH_ASSOC), action: 'delete');
            }*/
            
            return new Items(action: 'delete', itemsCount: $pdoStatement->rowCount());
        }
        
        return new Items(action: 'delete');
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
     * Returns true if supports returning items, otherwise false.
     *
     * @param string $method The methods such as insert, insertMany, update, delete.
     * @return bool
     */
    public function supportsReturningItems(string $method): bool
    {
        if ($method === 'insert') {
            return true;
        }
        
        return false;
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

    /**
     * Execute a statement.
     *
     * @param null|string $statement The statement.
     * @param array $bindings Any bindings for the statement.
     * @return PDOStatement
     */
    protected function execute(null|string $statement, array $bindings = []): PDOStatement
    {
        if (is_null($statement)) {
            throw new QueryException('', $bindings, 'Invalid Statement');
        }
        
        $pdoStatement = $this->pdo->prepare($statement);
        
        try {
            
            foreach ($bindings as $key => $value) {
                $pdoStatement->bindValue(
                    is_string($key) ? $key : $key + 1,
                    $value,
                    match (true) {
                        is_int($value) => PDO::PARAM_INT,
                        is_resource($value) => PDO::PARAM_LOB,
                        default => PDO::PARAM_STR
                    },
                );
            }
            
            $pdoStatement->execute();
            
            return $pdoStatement;
        } catch (Exception $e) {
            throw new QueryException($statement, $bindings, '', 0, $e);
        }
    }
}