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
 
namespace Tobento\Service\Storage\Grammar;

use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\StorageInterface;
use JsonException;

/**
 * Grammar
 */
abstract class Grammar implements GrammarInterface
{
    /**
     * @var TablesInterface
     */    
    protected TablesInterface $tables;
    
    /**
     * @var TablesInterface
     */    
    protected TablesInterface $queryTables;
    
    /**
     * @var null|string The table name.
     */    
    protected null|string $table = null;

    /**
     * @var null|string The statement.
     */    
    protected null|string $statement = null;
    
    /**
     * @var array The table joins.
     */    
    protected array $joins = [];

    /**
     * @var array The parameters to bind.
     */    
    protected array $bindings = [];
        
    /**
     * @var array The where constraints.
     */    
    protected array $wheres = [];

    /**
     * @var array The where constraints.
     */    
    protected array $groups = [];

    /**
     * @var array The where constraints.
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
     * @var null|string The index column for items.
     */    
    protected null|string $index = null;
            
    /**
     * @var null|string|array The select columns or null.
     */    
    protected null|string|array $select = null;

    /**
     * @var null|array The item to insert or null.
     */    
    protected null|array $insert = null;
    
    /**
     * @var null|iterable The items to insert or null.
     */    
    protected null|iterable $insertItems = null;

    /**
     * @var null|array The items to update or null.
     */    
    protected null|array $update = null;

    /**
     * @var null|array If to delete items.
     */    
    protected null|array $delete = null;
    
    /**
     * @var null|array The return columns or null.
     */    
    protected null|array $return = null;

    /**
     * @var null|array The item inserted or updated.
     */    
    protected null|array $item = null;
    
    /**
     * @var array The valid operators
     */    
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',    
    ];    
    
    /**
     * Create a new Grammar
     *
     * @param TablesInterface $tables
     */    
    public function __construct(TablesInterface $tables)
    {
        $this->tables = $tables;
        
        $this->queryTables = new Tables();
    }
        
    /**
     * The table name.
     *
     * @param string $table
     * @return static $this
     * @throws GrammarException
     */    
    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * The table joins.
     *
     * @param array $joins
     * @return static $this
     */    
    public function joins(array $joins): static
    {
        $this->joins = $joins;
        return $this;
    }

    /**
     * The bindings.
     *
     * @param array $bindings
     * @return static $this
     */    
    public function bindings(array $bindings): static
    {
        $this->bindings = $bindings;
        return $this;
    }

    /**
     * The wheres.
     *
     * @param array $wheres
     * @return static $this
     */    
    public function wheres(array $wheres): static
    {
        $this->wheres = $wheres;
        return $this;
    }

    /**
     * The groups.
     *
     * @param array $groups
     * @return static $this
     */    
    public function groups(array $groups): static
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * The havings.
     *
     * @param array $havings
     * @return static $this
     */    
    public function havings(array $havings): static
    {
        $this->havings = $havings;
        return $this;
    }

    /**
     * The orders.
     *
     * @param array $orders
     * @return static $this
     */    
    public function orders(array $orders): static
    {
        $this->orders = $orders;
        return $this;
    }
    
    /**
     * The limit.
     *
     * @param null|array $limit
     * @return static $this
     */    
    public function limit(null|array $limit): static
    {
        $this->limit = $limit;
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
     * The insert statement.
     *
     * @param array $item The item ['column' => 'value', ...]
     * @param null|array $return The columns to be returned.
     * @return static $this
     */    
    public function insert(array $item, null|array $return = []): static
    {
        $this->insert = $item;
        $this->return = $return;
        return $this;
    }
    
    /**
     * The insertItems statement.
     *
     * @param iterable $items
     * @param null|array $return The columns to be returned.
     * @return static $this
     */    
    public function insertItems(iterable $items, null|array $return = []): static
    {
        $this->insertItems = $items;
        $this->return = $return;
        return $this;
    }    

    /**
     * The update statement.
     *
     * @param array $items The items ['column' => 'value', ...]
     * @param null|array $return The columns to be returned.
     * @return static $this
     */    
    public function update(array $items, null|array $return = []): static
    {
        $this->update = $items;
        $this->return = $return;
        return $this;
    }

    /**
     * The delete statement.
     *
     * @param null|array $return The columns to be returned.
     * @return static $this
     */    
    public function delete(null|array $return = []): static
    {
        $this->delete = [];
        $this->return = $return;
        return $this;
    }    
    
    /**
     * The select columns.
     *
     * @param null|string|array $columns
     * @return static $this
     */    
    public function select(null|string|array $columns = null): static
    {
        $this->select = $columns ?: [];
        return $this;
    }
    
    /**
     * Get the statement
     *
     * @return null|string 'SELECT id, date_created FROM products' e.g. or null on failure
     * @throws GrammarException
     */    
    public function getStatement(): null|string
    {
        return null;
    }

    /**
     * Get the bindings.
     *
     * @return array
     */    
    public function getBindings(): array
    {
        return !empty($this->bindings) ? $this->bindings : [];
    }

    /**
     * Get the item inserted or updated.
     *
     * @return array
     */    
    public function getItem(): array
    {    
        return $this->item ?: [];
    }
    
    /**
     * Verify the operator.
     *
     * @param mixed $operator The operator such as '>'
     * @return null|string The verified operator or null if invalid.
     */    
    protected function verifyOperator(mixed $operator): null|string
    {
        if (!is_string($operator))
        {
            return null;
        }

        return in_array($operator, $this->operators) ? $operator : null;
    }
    
    /**
     * Verify the value.
     *
     * @param mixed $value Any value
     * @return mixed
     */    
    protected function verifyValue(mixed $value): mixed
    {
        if (is_null($value)) {
            return $value;
        }
        
        if (is_scalar($value)) {
            return $value;
        }

        return null;
    }
    
    /**
     * Encode Json value.
     *
     * @param mixed $value Any value
     * @return null|string
     */
    protected function encodeJsonValue(mixed $value): null|string
    {
        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }
    }
}