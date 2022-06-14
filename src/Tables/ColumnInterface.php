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
 
namespace Tobento\Service\Storage\Tables;

/**
 * ColumnInterface
 */
interface ColumnInterface
{
    /**
     * Returns the column.
     *
     * @return string
     */    
    public function column(): string;
    
    /**
     * Returns the table alias if any.
     *
     * @return null|string
     */    
    public function tableAlias(): null|string;
    
    /**
     * Returns a new instance with the specified table alias if any.
     *
     * @param null|string $alias
     * @return static
     */    
    public function withTableAlias(null|string $alias): static;
    
    /**
     * Returns the name of the column without alias and json.
     *
     * @return string
     */    
    public function name(): string;
    
    /**
     * Returns the alias of the column if any.
     *
     * @return null|string
     */    
    public function alias(): null|string;
    
    /**
     * Returns a new instance with the specified column alias if any.
     *
     * @param null|string $alias
     * @return static
     */    
    public function withAlias(null|string $alias): static;
        
    /**
     * Returns the json segments if any
     *
     * @return null|array
     */    
    public function jsonSegments(): null|array;
    
    /**
     * Returns a new instance with the specified json segments if any.
     *
     * @param null|array $segments
     * @return static
     */    
    public function withJsonSegments(null|array $segments): static;
}