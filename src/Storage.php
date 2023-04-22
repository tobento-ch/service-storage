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
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\Grammar\GrammarInterface;
use Tobento\Service\Storage\Query\SubQuery;
use Tobento\Service\Storage\Query\SubQueryWhere;
use Tobento\Service\Storage\Query\JoinClause;
use Generator;
use Closure;
use Throwable;

/**
 * Storage
 */
abstract class Storage implements StorageInterface
{
    /**
     * @var TablesInterface
     */    
    protected TablesInterface $tables;
    
    /**
     * @var null|string The table name.
     */    
    protected null|string $table = null;

    /**
     * @var array The table joins.
     */    
    protected array $joins = [];
    
    /**
     * @var array The where constraints.
     */    
    protected array $wheres = [];

    /**
     * @var array The group by constraints.
     */    
    protected array $groups = [];

    /**
     * @var array The havings constraints.
     */    
    protected array $havings = [];

    /**
     * @var array The orders constraints.
     */    
    protected array $orders = [];

    /**
     * @var null|array The limit constraints.
     */    
    protected null|array $limit = null;

    /**
     * @var array The parameters to bind.
     */    
    protected array $bindings = [];

    /**
     * @var null|array|string The select columns, raw select or null.
     */    
    protected null|array|string $select = null;

    /**
     * @var null|string The index column for items.
     */    
    protected null|string $index = null;

    /**
     * @var bool If to skip query.
     */    
    protected bool $skipQuery = false;
    
    /**
     * @var null|GrammarInterface The last grammar
     */    
    protected null|GrammarInterface $grammar = null;  

    /**
     * Create a new Storage.
     *
     * @param null|TablesInterface $tables
     */    
    public function __construct(
        null|TablesInterface $tables = null,
    ) {
        $this->tables = $tables ?: new Tables();
    }
    
    /**
     * Returns a new storage instance.
     *
     * @return static
     */
    public function new(): static
    {
        $new = clone $this;
        $new->tables = new Tables();
        return $new;
    }
    
    /**
     * Get the tables.
     *
     * @return TablesInterface
     */    
    public function tables(): TablesInterface
    {
        return $this->tables;
    }
    
    /**
     * Set the table name.
     *
     * @param string $table
     * @return static $this
     */    
    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Get the table name.
     *
     * @return string
     */    
    public function getTable(): string
    {
        return $this->table ?: '';
    }
    
    /**
     * Fetches the table items.
     *
     * @param string $table The table name.
     * @return iterable The items fetched.
     */
    public function fetchItems(string $table): iterable
    {
        return [];
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
        return [];
    }
    
    /**
     * Deletes the specified table.
     *
     * @param string $table The table name.
     * @return void
     */
    public function deleteTable(string $table): void
    {
        //
    }

    /**
     * The columns to select.
     *
     * @param string ...$columns
     * @return static $this
     */    
    public function select(string ...$columns): static
    {
        $this->select = $columns;
        return $this;
    }  

    /**
     * Get a single item by id.
     *
     * @param int|string $id
     * @return null|ItemInterface
     */
    public function find(int|string $id): null|ItemInterface
    {
        return null;
    }
    
    /**
     * Get a single item.
     *
     * @return null|ItemInterface
     */
    public function first(): null|ItemInterface
    {
        return null;
    }
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function get(): ItemsInterface
    {
        return new Items(action: 'get');
    }

    /**
     * Get a single column's value from the first item.
     *
     * @param string $column The column name
     * @return mixed
     */    
    public function value(string $column): mixed
    {
        $this->select($column);
        
        if (is_null($item = $this->first()?->all())) {
            return null;
        }
        
        return $item[array_key_first($item)] ?? null;
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
        return new Item(action: 'column');
    }

    /**
     * Get the count of the query.
     *
     * @return int
     */    
    public function count(): int
    {
        return 0;
    }

    /**
     * Insert an item.
     *
     * @param array $item The item data
     * @return ItemInterface The item inserted.
     */    
    public function insert(array $item): ItemInterface
    {
        return new Item(action: 'insert');
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
        return new Item(action: 'insert');
    }
    
    /**
     * Delete item(s).
     *
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The deleted items.
     */
    public function delete(null|array $return = []): ItemsInterface
    {
        return new Items(action: 'delete');
    }
    
    /**
     * Chunk item(s).
     *
     * @param int $length
     * @return Chunking
     */
    public function chunk(int $length = 10000): Chunking
    {
        return new Chunking($this, $length);
    }
            
    /**
     * Add a inner join to the query.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @return static $this
     */
    public function join(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null
    ): static {
        $this->addJoin($table, $first, $operator = '=', $second, 'inner');
        return $this;
    }
    
    /**
     * Add a left join to the query.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @return static $this
     */
    public function leftJoin(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null
    ): static {
        $this->addJoin($table, $first, $operator = '=', $second, 'left');
        return $this;
    }

    /**
     * Add a right join to the query.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @return static $this
     */
    public function rightJoin(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null
    ): static {
        $this->addJoin($table, $first, $operator = '=', $second, 'right');
        return $this;
    }
    
    /**
     * Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function where(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
    ): static {
        return $this->whereClause($column, $operator, $value, 'Base', 'and');
    }

    /**
     * Where column clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    public function whereColumn(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        return $this->whereClause($column, $operator, $value, 'Column', $boolean);
    }
    
    /**
     * Or Where column clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orWhereColumn(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
    ): static {
        return $this->whereClause($column, $operator, $value, 'Column', 'or');
    }
    
    /**
     * Or Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orWhere(string|Closure $column, string $operator = '=', mixed $value = null): static
    {
        return $this->whereClause($column, $operator, $value, 'Base', 'or');
    }

    /**
     * Where IN clause
     *
     * @param string|Closure $column The column name. 
     * @param array $value The values
     * @return static $this
     */
    public function whereIn(string|Closure $column, mixed $value = null): static
    {
        return $this->whereClause($column, 'IN', $value, 'In', 'and');
    }

    /**
     * Where or IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function orWhereIn(string|Closure $column, mixed $value = null): static
    {
        return $this->whereClause($column, 'IN', $value, 'In', 'or');
    }

    /**
     * Where NOT IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function whereNotIn(string|Closure $column, mixed $value = null): static
    {
        return $this->whereClause($column, 'NOT IN', $value, 'In', 'and');
    }

    /**
     * Where or NOT IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function orWhereNotIn(string|Closure $column, mixed $value = null): static
    {
        return $this->whereClause($column, 'NOT IN', $value, 'In', 'or');
    }

    /**
     * Where null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function whereNull(string|Closure $column): static
    {
        return $this->whereClause($column, '=', null, 'Null', 'and');
    }

    /**
     * Where or null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function orWhereNull(string|Closure $column): static
    {
        return $this->whereClause($column, '=', null, 'Null', 'or');
    }

    /**
     * Where not null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function whereNotNull(string|Closure $column): static
    {
        return $this->whereClause($column, '=', null, 'NotNull', 'and');
    }

    /**
     * Where or not null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function orWhereNotNull(string|Closure $column): static
    {
        return $this->whereClause($column, '=', null, 'NotNull', 'or');
    }
 
    /**
     * Where between clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function whereBetween(string|Closure $column, array $values): static
    {
        return $this->whereClause($column, '=', $values, 'Between', 'and');
    }
    
    /**
     * Where between or clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function orWhereBetween(string|Closure $column, array $values): static
    {
        return $this->whereClause($column, '=', $values, 'Between', 'or');
    }
    
    /**
     * Where not between clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function whereNotBetween(string|Closure $column, array $values): static
    {
        return $this->whereClause($column, '!=', $values, 'NotBetween', 'and');
    }
    
    /**
     * Where not between or clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function orWhereNotBetween(string|Closure $column, array $values): static
    {
        return $this->whereClause($column, '!=', $values, 'NotBetween', 'or');
    }
    
    /**
     * Where Json contains clause
     *
     * @param string $column The column name.
     * @param mixed $value
     * @param string $boolean
     * @param bool $not
     * @return static $this
     */
    public function whereJsonContains(
        string $column,
        mixed $value,
        string $boolean = 'and',
        bool $not = false
    ): static {
        $operator = $not === true ? '=' : '!=';
        return $this->whereClause($column, $operator, $value, 'JsonContains', $boolean);
    }
    
    /**
     * Where Json contains or clause
     *
     * @param string $column The column name.
     * @param mixed $value
     * @return static $this
     */
    public function orWhereJsonContains(
        string $column,
        mixed $value,
    ): static {
        return $this->whereJsonContains($column, $value, 'or');
    }
    
    /**
     * Where Json contains key clause
     *
     * @param string $column The column name.
     * @param string $boolean
     * @param bool $not
     * @return static $this
     */
    public function whereJsonContainsKey(
        string $column,
        string $boolean = 'and',
        bool $not = false
    ): static {
        $operator = $not === true ? '=' : '!=';
        return $this->whereClause($column, $operator, null, 'JsonContainsKey', $boolean);
    }
    
    /**
     * Where Json contains key or clause
     *
     * @param string $column The column name.
     * @param string $boolean
     * @param bool $not
     * @return static $this
     */
    public function orWhereJsonContainsKey(
        string $column,
    ): static {
        return $this->whereJsonContainsKey($column, 'or');
    }
    
    /**
     * Where Json length clause
     *
     * @param string $column The column name.
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return static $this
     */
    public function whereJsonLength(
        string $column,
        string $operator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        return $this->whereClause($column, $operator, $value, 'JsonLength', $boolean);
    }
    
    /**
     * Where Json length or clause
     *
     * @param string $column The column name.
     * @param string $operator
     * @param mixed $value
     * @return static $this
     */
    public function orWhereJsonLength(
        string $column,
        string $operator = '=',
        mixed $value = null
    ): static {
        return $this->whereJsonLength($column, $operator, $value, 'or');
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
        return $this->whereClause($value, '=', $bindings, 'Raw', 'and');
    }
    
    /**
     * Group by clause.
     *
     * @param array|string $groups The column(s) name.
     * @return static $this
     */
    public function groupBy(...$groups): static
    {
        foreach($groups as $column) {
            $this->groups[] = ['column' => $column, 'type' => 'Base'];
        }

        return $this;
    }
    
    /**
     * Having clause.
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    public function having(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        $type = 'Base';
        
        $this->havings[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );
        
        return $this;
    }
    
    /**
     * Or having clause.
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orHaving(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
    ): static {
        return $this->having($column, $operator, $value, 'or');
    }    

    /**
     * Having between clause.
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The value to compare. [4, 5]
     * @param bool $not
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    public function havingBetween(
        string|Closure $column,
        mixed $value = null,
        bool $not = false,
        string $boolean = 'and'
    ): static {
        
        $type = 'Between';
        
        $this->havings[] = compact(
            'type', 'column', 'value', 'boolean', 'not'
        );
        
        return $this;
    }
        
    /**
     * Order clause.
     *
     * @param string $column The column name.
      * @param string $direction
     * @return static $this
     */
    public function order(string $column, string $direction = 'ASC'): static
    {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }
            
    /**
     * Limit clause.
     *
     * @param null|int $number The number of rows to be returned. 
      * @param int $offset The offset where to start.
     * @return static $this
     */
    public function limit(null|int $number, int $offset = 0): static
    {
        if ($number === null)
        {
            $this->limit = null;
            return $this;
        }
        
        $this->limit = [$number, $offset];
        return $this;
    }

    /**
     * The index column for items.
     *
     * @param null|string $column The column name.
     * @return static $this
     */    
    public function index(null|string $column): static
    {
        $this->index = $column;
        return $this;
    }
        
    /**
     * Get the select
     *
     * @return null|array|string
     */
    public function getSelect(): null|array|string
    {
        return $this->select;
    }

    /**
     * Get the joins.
     *
     * @return array
     */    
    public function getJoins(): array
    {
        return $this->joins;
    }
    
    /**
     * Get the wheres
     *
     * @return array
     */
    public function getWheres(): array
    {
        return $this->wheres;
    }

    /**
     * Set the wheres
     *
     * @param array $wheres
     * @return static $this
     */
    public function setWheres(array $wheres): static
    {
        $this->wheres = $wheres;
        return $this;
    }

    /**
     * Get the groups
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Get the havings
     *
     * @return array
     */
    public function getHavings(): array
    {
        return $this->havings;
    }
    
    /**
     * Get the orders
     *
     * @return array
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * Get the limit
     *
     * @return null|array
     */
    public function getLimit(): null|array
    {
        return $this->limit;
    }

    /**
     * Get the index column for items.
     *
     * @return null|string The column name.
     */    
    public function getIndex(): null|string
    {
        return $this->index;
    }

    /**
     * Get the bindings
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get the query.
     *
     * param null|Closure $callback
     * @return array [statement, bindings[]]
     */    
    public function getQuery(null|Closure $callback = null): array
    {
        return [];
    }

    /**
     * Create a new SubQuery
     *
     * @param string $statement The statement
     * @param array $bindings The bindings
     * @return SubQuery
     */    
    public function createSubQuery(string $statement, array $bindings = []): SubQuery
    {
        return new SubQuery($statement, $bindings);
    }

    /**
     * Begin a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function begin(): bool
    {
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
        return true;
    }
    
    /**
     * Execute a transaction.
     *
     * @param callable $callback
     * @return void
     * @throws Throwable
     */
    public function transaction(callable $callback): void
    {
        $this->begin();

        try {
            $callback($this);
            $this->commit();
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Returns true if supporting nested transactions, otherwise false.
     *
     * @return bool
     */
    public function supportsNestedTransactions(): bool
    {
        return false;
    }
    
    /**
     * Returns true if supports returning items, otherwise false.
     *
     * @param string $method The methods such as insert, insertMany, update, delete.
     * @return bool
     */
    public function supportsReturningItems(string $method): bool
    {
        return false;
    }
    
    /**
     * Clear query
     *
     * @return static $this
     */
    public function clear(): static
    {
        $this->joins = [];
        $this->select = null;
        $this->wheres = [];
        $this->groups = [];
        $this->havings = [];
        $this->bindings = [];
        $this->orders = [];
        $this->limit = null;
        $this->index = null;
        
        return $this;
    }
    
    /**
     * Get last grammar used.
     *
     * @return null|GrammarInterface
     */    
    public function grammar(): null|GrammarInterface
    {
        return $this->grammar;
    }

    /**
     * Add a join clause.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @param string $direction The direction
     * @return static $this
     */
    protected function addJoin(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null,
        string $direction = 'left'
    ): static {
        
        if ($first instanceof Closure)
        {
            $join = new JoinClause(clone $this, $table, $direction, $first);
        }
        else
        {
            $join = new JoinClause(clone $this, $table, $direction);
            $join->on($first, $operator, $second);
        }
        
        $this->joins[] = $join;
        
        return $this;
    }
    
    /**
     * Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @param string $type The type such as Raw, In, NotIn, Null, NotNull, Between, NotBetween
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    protected function whereClause(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
        string $type = 'Base',
        string $boolean = 'and'
    ): static {
        if ($column instanceof Closure) {
            $column = new SubQueryWhere($column, clone $this);
        }
        
        if ($value instanceof Closure) {
            $value = new SubQueryWhere($value, clone $this);
        }    

        $this->wheres[] = compact(
            'type', 'column', 'operator', 'value', 'boolean'
        );
        
        return $this;
    }
}