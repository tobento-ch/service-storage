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
 
namespace Tobento\Service\Storage\Query;

use Tobento\Service\Storage\StorageInterface;
use Closure;

/**
 * JoinClause
 */
class JoinClause
{    
    /**
     * Create a new JoinClause.
     *
     * @param StorageInterface $storage
     * @param string $table The table name
     * @param string $direction The direction
     * @param null|Closure $callback
     */    
    public function __construct(
        protected StorageInterface $storage,
        protected string $table,
        protected string $direction = 'left',
        protected null|Closure $callback = null
    ) {        
        $this->storage->clear();
    }

    /**
     * Add an on clause.
     *
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param string $second The second column name
     * @param string $boolean The boolean such as 'and', 'or'
     * @return JoinClause
     */    
    public function on(string|Closure $first, string $operator, string $second, string $boolean = 'and'): JoinClause
    {            
        $this->storage->whereColumn($first, $operator, $second, $boolean);
        
        return $this;
    }

    /**
     * Add an or or clause.
     *
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param string $second The second column name
     * @param string $boolean The boolean such as 'and', 'or'
     * @return JoinClause
     */    
    public function orOn(string|Closure $first, string $operator, string $second, string $boolean = 'or'): JoinClause
    {
        $this->on($first, $operator, $second, $boolean);
        
        return $this;
    }

    /**
     * Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return JoinClause
     */
    public function where(string|Closure $column, string $operator = '=', mixed $value = null): JoinClause
    {
        $this->storage->where($column, $operator, $value);
        
        return $this;
    }

    /**
     * Or Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return JoinClause
     */
    public function orWhere(string|Closure $column, string $operator = '=', mixed $value = null): JoinClause
    {
        $this->storage->orWhere($column, $operator, $value);
        
        return $this;
    }
    
    /**
     * Get the table.
     *
     * @return string
     */    
    public function table(): string
    {
        return $this->table;
    }
    
    /**
     * Get the direction.
     *
     * @return string
     */    
    public function direction(): string
    {
        return $this->direction;
    }
    
    /**
     * Get the callback.
     *
     * @return null|Closure
     */    
    public function callback(): null|Closure
    {
        return $this->callback;
    }

    /**
     * Get the storage.
     *
     * @return StorageInterface
     */    
    public function storage(): StorageInterface
    {
        return $this->storage;
    }
}