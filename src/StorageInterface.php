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

use Tobento\Service\Storage\Grammar\GrammarInterface;
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\Query\SubQuery;
use Closure;

/**
 * StorageInterface
 */
interface StorageInterface
{
   /**
     * Get the tables.
     *
     * @return TablesInterface
     */    
    public function tables(): TablesInterface;
    
    /**
     * Set the table name.
     *
     * @param string $table
     * @return static $this
     */    
    public function table(string $table);

    /**
     * Get the table name.
     *
     * @return string
     */    
    public function getTable();
    
    /**
     * Fetches the table items.
     *
     * @param string $table The table name.
     * @return iterable The items fetched.
     */
    public function fetchItems(string $table): iterable;

    /**
     * Stores the table items.
     *
     * @param string $table The table name.
     * @param iterable $items The items to store.
     * @return iterable The stored items.
     */
    public function storeItems(string $table, iterable $items): iterable;

    /**
     * The columns to select.
     *
     * @param string ...$columns
     * @return static $this
     */    
    public function select(string ...$columns): static;

    /**
     * Get a single item by id.
     *
     * @param int|string $id
     * @return null|ItemInterface
     */
    public function find(int|string $id): null|ItemInterface;
    
    /**
     * Get a single item.
     *
     * @return null|ItemInterface
     */
    public function first(): null|ItemInterface;
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function get(): ItemsInterface;

    /**
     * Get a single column's value from the first item.
     *
     * @param string $column The column name
     * @return mixed
     */    
    public function value(string $column): mixed;

    /**
     * Get column's value from the items.
     *
     * @param string $column The column name
     * @param null|string $key The column name for the index
     * @return ItemInterface
     */
    public function column(string $column, null|string $key = null): ItemInterface;

    /**
     * Get the count of the query.
     *
     * @return int
     */    
    public function count(): int;

    /**
     * Insert an item.
     *
     * @param array $item The item data
     * @return null|ItemInterface The item on success, otherwise null.
     */    
    public function insert(array $item): null|ItemInterface;
    
    /**
     * Insert items.
     *
     * @param iterable $items
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface
     */
    public function insertItems(iterable $items, null|array $return = []): ItemsInterface;
    
    /**
     * Update item(s).
     *
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The updated items.
     */
    public function update(array $item, null|array $return = []): ItemsInterface;

    /**
     * Update or insert item(s).
     *
     * @param array $attributes The attributes to query.
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return null|ItemInterface|ItemsInterface The item(s) on success, otherwise null.
     */
    public function updateOrInsert(
        array $attributes,
        array $item,
        null|array $return = []
    ): null|ItemInterface|ItemsInterface;
    
    /**
     * Delete item(s).
     *
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The deleted items.
     */
    public function delete(null|array $return = []): ItemsInterface;
            
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
    ): static;
    
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
    ): static;

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
    ): static;
    
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
    ): static;

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
    ): static;
    
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
    ): static;
    
    /**
     * Or Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orWhere(string|Closure $column, string $operator = '=', mixed $value = null): static;

    /**
     * Where IN clause
     *
     * @param string|Closure $column The column name. 
     * @param array $value The values
     * @return static $this
     */
    public function whereIn(string|Closure $column, mixed $value = null): static;

    /**
     * Where or IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function orWhereIn(string|Closure $column, mixed $value = null): static;

    /**
     * Where NOT IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function whereNotIn(string|Closure $column, mixed $value = null): static;

    /**
     * Where or NOT IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function orWhereNotIn(string|Closure $column, mixed $value = null): static;

    /**
     * Where null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function whereNull(string|Closure $column): static;

    /**
     * Where or null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function orWhereNull(string|Closure $column): static;

    /**
     * Where not null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function whereNotNull(string|Closure $column): static;

    /**
     * Where or not null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function orWhereNotNull(string|Closure $column): static;
 
    /**
     * Where between clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function whereBetween(string|Closure $column, array $values): static;
    
    /**
     * Where between or clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function orWhereBetween(string|Closure $column, array $values): static;
    
    /**
     * Where not between clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function whereNotBetween(string|Closure $column, array $values): static;
    
    /**
     * Where not between or clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function orWhereNotBetween(string|Closure $column, array $values): static;
    
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
    ): static;
    
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
    ): static;
    
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
    ): static;
    
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
    ): static;
    
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
    ): static;
    
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
    ): static;  
    
    /**
     * Where Raw clause
     *
     * @param string $value The raw value. 
     * @param array $bindings Any bindings
     * @return static $this
     */
    public function whereRaw(string $value, array $bindings = []): static;
    
    /**
     * Group by clause.
     *
     * @param array|string $groups The column(s) name.
     * @return static $this
     */
    public function groupBy(...$groups): static;
    
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
    ): static;
    
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
    ): static;  

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
    ): static;
        
    /**
     * Order clause.
     *
     * @param string $column The column name.
      * @param string $direction
     * @return static $this
     */
    public function order(string $column, string $direction = 'ASC'): static;
            
    /**
     * Limit clause.
     *
     * @param null|int $number The number of rows to be returned. 
      * @param int $offset The offset where to start.
     * @return static $this
     */
    public function limit(null|int $number, int $offset = 0): static;

    /**
     * The index column for items.
     *
     * @param null|string $column The column name.
     * @return static $this
     */    
    public function index(null|string $column): static;
        
    /**
     * Get the select
     *
     * @return null|array|string
     */
    public function getSelect(): null|array|string;

    /**
     * Get the joins.
     *
     * @return array
     */    
    public function getJoins(): array;
    
    /**
     * Get the wheres
     *
     * @return array
     */
    public function getWheres(): array;

    /**
     * Set the wheres
     *
     * @param array $wheres
     * @return static $this
     */
    public function setWheres(array $wheres): static;

    /**
     * Get the groups
     *
     * @return array
     */
    public function getGroups(): array;

    /**
     * Get the havings
     *
     * @return array
     */
    public function getHavings(): array;
    
    /**
     * Get the orders
     *
     * @return array
     */
    public function getOrders(): array;

    /**
     * Get the limit
     *
     * @return null|array
     */
    public function getLimit(): null|array;

    /**
     * Get the index column for items.
     *
     * @return null|string The column name.
     */    
    public function getIndex(): null|string;

    /**
     * Get the bindings
     *
     * @return array
     */
    public function getBindings(): array;

    /**
     * Get the query.
     *
     * param null|Closure $callback
     * @return array [statement, bindings[]]
     */    
    public function getQuery(null|Closure $callback = null): array;

    /**
     * Create a new SubQuery
     *
     * @param string $statement The statement
     * @param array $bindings The bindings
     * @return SubQuery
     */    
    public function createSubQuery(string $statement, array $bindings = []): SubQuery;
    
    /**
     * Begin a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function begin(): bool;
    
    /**
     * Commit a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function commit(): bool;

    /**
     * Rollback a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function rollback(): bool;
    
    /**
     * Returns true if supporting nested transactions, otherwise false.
     *
     * @return bool
     */
    public function supportsNestedTransactions(): bool;
    
    /**
     * Clear query
     *
     * @return static $this
     */
    public function clear(): static;
    
    /**
     * Get last grammar used.
     *
     * @return null|GrammarInterface
     */    
    public function grammar(): null|GrammarInterface;
}