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
 * Tables
 */
class Tables implements TablesInterface
{
    /**
     * @var array<string, Table>
     */    
    protected array $tables = [];
    
    /**
     * @var array<int|string, string> ['tableAlias' => 'tableName']
     */    
    protected array $tableAliases = [];
    
    /**
     * @var array<string, string> ['columnName' => 'tableName']
     */    
    protected array $columnNamesToTableNames = [];    

    /**
     * Create a new Tables.
     *
     * @param null|TableFactoryInterface $tableFactory
     * @param null|ColumnFactoryInterface $columnFactory
     */    
    public function __construct(
        protected null|TableFactoryInterface $tableFactory = null,
        protected null|ColumnFactoryInterface $columnFactory = null,
    ) {
        $this->columnFactory = $columnFactory ?: new ColumnFactory();
        $this->tableFactory = $tableFactory ?: new TableFactory($this->columnFactory);
    }

    /**
     * Add a table.
     *
     * @param string $table
     * @param array<int, string> $columns The columns ['foo', 'bar']
     * @param null|string $primaryKey The primary key for the table if any.
     * @return static $this
     */    
    public function add(string $table, array $columns, null|string $primaryKey = null): static
    {
        $table = $this->tableFactory->createTable($table, $columns, $primaryKey);
        
        $this->addTable($table);
        
        return $this;        
    }
    
    /**
     * Add a table.
     *
     * @param TableInterface $table
     * @return static $this
     */    
    public function addTable(TableInterface $table): static
    {        
        $this->tables[$table->name()] = $table;
        
        if ($table->alias())
        {
            $this->tableAliases[$table->alias()] = $table->name();
        }
        
        foreach($table->getColumnNames() as $columnName)
        {
            $this->columnNamesToTableNames[$columnName] = $table->name();
        }
            
        return $this;        
    }
    
    /**
     * Remove a table.
     *
     * @param string $table
     * @return static $this
     */    
    public function removeTable(string $table): static
    {
        $table = $this->tableFactory->createTable($table);
        
        unset($this->tables[$table->name()]);
        
        if ($table->alias())
        {
            unset($this->tableAliases[$table->alias()]);
        }
        
        foreach($this->columnNamesToTableNames as $columnName => $tableName)
        {
            if ($tableName === $table->name())
            {
                unset($this->columnNamesToTableNames[$columnName]);
            }
        }
            
        return $this;        
    }
    
    /**
     * Verify a table.
     *
     * @param null|string|TableInterface $table
     * @return null|TableInterface
     */    
    public function verifyTable(null|string|TableInterface $table): null|TableInterface
    {
        if (is_null($table)) {
            return null;
        }
        
        if (is_string($table)) {
            $table = $this->tableFactory->createTable($table);
        }
             
        if (is_null($verifiedTable = $this->getTable($table))) {
            return null;
        }
        
        return $verifiedTable->withAlias($table->alias());
    }

    /**
     * Returns the table or null.
     *
     * @param TableInterface|string $table
     * @return null|Table
     */    
    public function getTable(TableInterface|string $table): null|TableInterface
    {
        if (is_string($table)) {
            $table = $this->tableFactory->createTable($table);
        }
        
        return $this->tables[$table->name()] ?? null;
    }
    
    /**
     * Returns the primary key for the specified table or null if none.
     *
     * @param TableInterface|string $table
     * @return null|string
     */    
    public function getPrimaryKey(TableInterface|string $table): null|string
    {
        if (is_null($table = $this->getTable($table))) {
            return null;
        }
        
        return $table->primaryKey();
    } 
    
    /**
     * Returns the column or null.
     *
     * @param ColumnInterface|string $column
     * @return null|ColumnInterface
     */    
    public function getColumn(ColumnInterface|string $column): null|ColumnInterface
    {
        if (is_string($column)) {
            $column = $this->columnFactory->createColumn($column);
        }
        
        if (is_null($tableName = $this->getTableNameFromColumn($column))) {
            return null;
        }
        
        if (is_null($table = $this->getTable($tableName))) {
            return null;
        }
        
        return $table->getColumn($column);
    }
    
    /**
     * Verify a column.
     *
     * @param ColumnInterface|string $column
     * @return null|ColumnInterface
     */    
    public function verifyColumn(ColumnInterface|string $column): null|ColumnInterface
    {
        if (is_string($column)) {
            $column = $this->columnFactory->createColumn($column);
        }
        
        if (is_null($tableName = $this->getTableNameFromColumn($column))) {
            return null;
        }
        
        if (is_null($table = $this->getTable($tableName))) {
            return null;
        }
        
        return $column->withTableAlias($table->alias());     
    }
    
    /**
     * Returns a new instance with the specified columns.
     *
     * @param array<int, string> $columns
     * @return static
     */    
    public function withColumns(array $columns): static
    {
        $new = clone $this;
        $new->tables = [];
        $new->tableAliases = [];
        $new->columnNamesToTableNames = [];
        
        foreach($this->tables as $table)
        {
            $new->addTable($table->withColumns($columns));
        }

        return $new;
    }
    
    /**
     * Returns the columns
     *
     * @return array<string, ColumnInterface>
     */
    public function getColumns(): array
    {
        $columns = [];
        
        foreach($this->tables as $table)
        {            
            $columns = array_merge($columns, $table->columns());
        }
            
        return $columns;
    }
    
    /**
     * Returns the column names.
     *
     * @return array<int, string>
     */    
    public function getColumnNames(): array
    {
        $names = [];
        
        foreach($this->tables as $table)
        {            
            $names = array_merge($names, $table->getColumnNames());
        }
        
        return $names;
    }

    /**
     * Get table name from column.
     *
     * @param ColumnInterface $column
     * @return null|string
     */    
    protected function getTableNameFromColumn(ColumnInterface $column): null|string
    {
        /*
        This might get wrong table columns. So do not use this.
        if ($column->tableAlias()) {
            return $this->tableAliases[$column->tableAlias()] ?? null;
        }*/
        
        return $this->columnNamesToTableNames[$column->name()] ?? null;
    }
}