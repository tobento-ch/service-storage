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
 * TableInterface
 */
interface TableInterface
{
    /**
     * Returns the table name with alias if any.
     *
     * @return string
     */    
    public function table(): string;
    
    /**
     * Returns the name of the table without alias.
     *
     * @return string
     */    
    public function name(): string;
    
    /**
     * Returns the alias of the table if any.
     *
     * @return null|string
     */    
    public function alias(): null|string;
    
    /**
     * Returns the columns.
     *
     * @return array<string, ColumnInterface>
     */    
    public function columns(): array;
    
    /**
     * Returns the primary key for the table if any.
     *
     * @return null|string
     */    
    public function primaryKey(): null|string;
    
    /**
     * Returns the column or null.
     *
     * @param ColumnInterface|string $column
     * @return null|ColumnInterface
     */    
    public function getColumn(ColumnInterface|string $column): null|ColumnInterface;
    
    /**
     * Returns a new instance with the specified columns.
     *
     * @param array<int, string> $columns
     * @return static
     */    
    public function withColumns(array $columns): static;

    /**
     * Returns a new instance with the specified table alias.
     *
     * @param null|string $alias
     * @return static
     */    
    public function withAlias(null|string $alias): static;

    /**
     * Returns the column names.
     *
     * @return array<int, string>
     */    
    public function getColumnNames(): array;
}