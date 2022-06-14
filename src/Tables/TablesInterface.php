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
 * TablesInterface
 */
interface TablesInterface
{
    /**
     * Add a table.
     *
     * @param string $table
     * @param array<int, string> $columns The columns ['foo', 'bar']
     * @param null|string $primaryKey The primary key for the table if any.
     * @return static $this
     */    
    public function add(string $table, array $columns, null|string $primaryKey = null): static;
    
    /**
     * Add a table.
     *
     * @param TableInterface $table
     * @return static $this
     */    
    public function addTable(TableInterface $table): static;
    
    /**
     * Remove a table.
     *
     * @param string $table
     * @return static $this
     */    
    public function removeTable(string $table): static;
    
    /**
     * Verify a table.
     *
     * @param null|string|TableInterface $table
     * @return null|TableInterface
     */    
    public function verifyTable(null|string|TableInterface $table): null|TableInterface;

    /**
     * Returns the table or null.
     *
     * @param TableInterface|string $table
     * @return null|Table
     */    
    public function getTable(TableInterface|string $table): null|TableInterface;
    
    /**
     * Returns the primary key for the specified table or null if none.
     *
     * @param TableInterface|string $table
     * @return null|string
     */    
    public function getPrimaryKey(TableInterface|string $table): null|string;
    
    /**
     * Returns the column or null.
     *
     * @param ColumnInterface|string $column
     * @return null|ColumnInterface
     */    
    public function getColumn(ColumnInterface|string $column): null|ColumnInterface;
    
    /**
     * Verify a column.
     *
     * @param ColumnInterface|string $column
     * @return null|ColumnInterface
     */    
    public function verifyColumn(ColumnInterface|string $column): null|ColumnInterface;
    
    /**
     * Returns a new instance with the specified columns.
     *
     * @param array<int, string> $columns
     * @return static
     */    
    public function withColumns(array $columns): static;
    
    /**
     * Returns the columns
     *
     * @return array<string, ColumnInterface>
     */
    public function getColumns(): array;
    
    /**
     * Returns the column names.
     *
     * @return array<int, string>
     */    
    public function getColumnNames(): array;
}