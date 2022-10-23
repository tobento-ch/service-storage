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

use Tobento\Service\Storage\Grammar\StorableTablesGrammar;
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Collection\Arr;
use JsonException;
use Closure;

/**
 * InMemoryStorage
 */
class InMemoryStorage extends Storage
{
    /**
     * @var int
     */    
    protected int $transactionLevel = 0;
    
    /**
     * @var array
     */    
    protected array $transactionItems = [];    
    
    /**
     * Create a new InMemoryStorage.
     *
     * @param array $items The items
     * @param null|TablesInterface $tables
     */    
    public function __construct(
        protected array $items,
        null|TablesInterface $tables = null,
    ) {
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
        
        return $this->items[$table->name()] ?? [];
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
        
        if (
            $this->transactionLevel > 0
            && !isset($this->transactionItems[$this->transactionLevel][$table->name()])
        ) {
            $this->transactionItems[$this->transactionLevel][$table->name()] = $this->items[$table->name()];
        }
        
        return $this->items[$table->name()] = $items;
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
        $items = $this->get()->all();
        
        $data = $items[array_key_first($items)] ?? null;
        
        return is_array($data) ? new Item($data) : null;
    }
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function get(): ItemsInterface
    {
        $grammar = (new StorableTablesGrammar($this, $this->tables()))
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
        
        $this->grammar = $grammar;

        if ($this->skipQuery) {
            return new Items();
        }
        
        $data = $grammar->execute();
        
        $this->clear();
        
        return new Items(is_array($data) ? $data : []);
    }
    
    /**
     * Get a single column's value from the first item.
     *
     * @param string $column The column name
     * @return mixed
     */    
    public function value(string $column): mixed
    {
        if (is_null($col = $this->tables()->verifyColumn($column))) {
            return parent::value($column);
        }
                
        $this->select($col->column());
        
        if (is_null($item = $this->first()?->all())) {
            return null;
        }
        
        $value = $item[array_key_first($item)] ?? null;
        
        if (!$col->jsonSegments() || !is_string($value)) {
            return $value;
        }

        try {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return Arr::get($value, implode('.', $col->jsonSegments()));
        } catch (JsonException $e) {
            return $value;
        }
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
        
        $items = $this->get()->all();
        $columnKey = null;

        if (!is_null($key)) {
            $columnKey = $this->tables()->verifyColumn($key);
        }
        
        if (
            $column->jsonSegments()
            || ($columnKey && $columnKey->jsonSegments())
        ) {
            
            $columnPath = null;
            $keyPath = null;
                
            if ($column->jsonSegments()) {
                $columnPath = implode('.', $column->jsonSegments());   
            }
            
            if ($columnKey && $columnKey->jsonSegments()) {
                $keyPath = implode('.', $columnKey->jsonSegments());   
            }            
            
            foreach($items as $i => $item) {
                foreach($item as $k => $value) {
                    try {
                        if ($columnPath && is_string($value) && $column->name() === $k) {
                            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                            $items[$i][$k] = Arr::get($value, $columnPath);
                            $value = json_encode($value, JSON_THROW_ON_ERROR);
                        }
                    } catch (JsonException $e) {
                        $items[$i][$k] = is_scalar($value) ? $value : '';
                    }
                    try {
                        if ($keyPath && is_string($value) && $columnKey?->name() === $k) {
                            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                            $keyVal = Arr::get($value, $keyPath);
                            $keyVal = is_scalar($keyVal) ? (string)$keyVal : 'null';
                            $items[$i][$columnKey?->column()] = $keyVal;
                        }
                    } catch (JsonException $e) {
                        $items[$i][$columnKey?->column()] = is_scalar($value) ? $value : '';
                    }                    
                }
            }
        }
        
        return new Item(array_column($items, $column->name(), $columnKey?->name()));
    }

    /**
     * Get the count of the query.
     *
     * @return int
     */    
    public function count(): int
    {
        return $this->get()->count();
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
        throw new UnsupportedStorageException('selectRaw is not supported!');
    }
    
    /**
     * The raw expression to add to the select()
     *
     * @param string $expression The expression to add.
     * @return static $this
     */    
    public function selectAddRaw(string $expression): static
    {
        throw new UnsupportedStorageException('selectAddRaw is not supported!');
    }

    /**
     * Where Raw clause
     *
     * @param string $value The raw value. 
     * @param array $bindings Any bindings
     * @return static $this
     */
    public function whereRaw(string $value, array $bindings = []): static
    {
        throw new UnsupportedStorageException('whereRaw is not supported!');
    }

    /**
     * Insert item(s).
     *
     * @param array $item The item data
     * @return null|ResultInterface The result on success, otherwise null.
     */    
    public function insert(array $item): null|ResultInterface
    {
        if (empty($item)) {
            return null;    
        }
        
        $grammar = (new StorableTablesGrammar($this, $this->tables()))
            ->table($this->table ?? '')
            ->insert($item);
        
        $this->grammar = $grammar;

        if ($this->skipQuery) {
            return null;
        }
        
        $this->clear();
        
        $grammar->execute();
        
        return new Result(
            action: 'insert',
            item: new Item($grammar->getItem()),
            items: new Items(),
        );
    }

    /**
     * Update item(s).
     *
     * @param array $item The item data
     * @return null|ResultInterface The result on success, otherwise null.
     */
    public function update(array $item): null|ResultInterface
    {
        if (empty($item)) {
            return null;    
        }
        
        $grammar = (new StorableTablesGrammar($this, $this->tables()))
            ->table($this->table ?? '')
            ->wheres($this->wheres)
            ->orders($this->orders)
            ->limit($this->limit)
            ->update($item);
        
        $this->grammar = $grammar;
        
        if ($this->skipQuery) {
            return null;
        }
        
        $this->clear();
        
        $items = $grammar->execute();
        
        return new Result(
            action: 'update',
            item: new Item($grammar->getItem()),
            items: new Items(!is_null($items) ? $items : []),
        );
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
        
        // insert
        return $this->insert($attributes + $item);
    }
    
    /**
     * Delete item(s).
     *
     * @return null|ResultInterface The result on success, otherwise null.
     */    
    public function delete(): null|ResultInterface
    {
        $grammar = (new StorableTablesGrammar($this, $this->tables()))
            ->table($this->table ?? '')
            ->wheres($this->wheres)
            ->orders($this->orders)
            ->limit($this->limit)
            ->delete();
        
        $this->grammar = $grammar;
        
        if ($this->skipQuery) {
            return null;
        }
        
        $this->clear();
        
        $items = $grammar->execute();
        
        return new Result(
            action: 'delete',
            item: new Item(),
            items: new Items(!is_null($items) ? $items : []),
        );
    }
    
    /**
     * Begin a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function begin(): bool
    {
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
        return true;
    }
    
    /**
     * Rollback a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function rollback(): bool
    {
        if (!array_key_exists($this->transactionLevel, $this->transactionItems)) {
            $this->transactionLevel--;
            return true;
        }
        
        foreach($this->transactionItems[$this->transactionLevel] as $table => $items)
        {
            $this->storeItems(
                $table,
                $items,
            );

            unset($this->transactionItems[$this->transactionLevel][$table]);
        }
        
        $this->transactionLevel--;
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
        
        $grammar = (new StorableTablesGrammar($this, $this->tables()))
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