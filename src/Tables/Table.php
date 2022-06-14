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
 * Table
 */
class Table implements TableInterface
{
    /**
     * @var string
     */    
    protected string $name;
    
    /**
     * @var null|string
     */    
    protected null|string $alias;

    /**
     * @var array<string, ColumnInterface>
     */    
    protected array $columns = [];
    
    /**
     * @var array
     */    
    protected array $columnNames = [];    
    
    /**
     * Create a new Table.
     *
     * @param string $table
     * @param array<int, string> $columns
     * @param null|string $primaryKey
     * @param null|ColumnFactoryInterface $columnFactory
     */    
    public function __construct(
        protected string $table,
        array $columns = [],
        protected null|string $primaryKey = null,
        protected null|ColumnFactoryInterface $columnFactory = null,
    ) {
        $this->columnFactory = $columnFactory ?: new ColumnFactory();
        
        [$this->name, $this->alias] = $this->parseTable($table);
        
        $this->columns = $this->createColumns($columns);     
    }

    /**
     * Returns the table name with alias if any.
     *
     * @return string
     */    
    public function table(): string
    {
        return $this->table;
    }
    
    /**
     * Returns the name of the table without alias.
     *
     * @return string
     */    
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the alias of the table if any.
     *
     * @return null|string
     */    
    public function alias(): null|string
    {
        return $this->alias;
    }
    
    /**
     * Returns the columns.
     *
     * @return array<string, ColumnInterface>
     */    
    public function columns(): array
    {
        return $this->columns;
    }
    
    /**
     * Returns the primary key for the table if any.
     *
     * @return null|string
     */    
    public function primaryKey(): null|string
    {
        return $this->primaryKey;
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
        
        if (isset($this->columns[$column->column()])) {
            return $this->columns[$column->column()];
        }    
        
        $columnName = $this->columnNames[$column->name()] ?? '';
        
        return $this->columns[$columnName] ?? null;
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
        $new->columns = $new->createColumns($columns, array_keys($this->columnNames));
        return $new;
    }

    /**
     * Returns a new instance with the specified table alias.
     *
     * @param null|string $alias
     * @return static
     */    
    public function withAlias(null|string $alias): static
    {
        $new = clone $this;
        $new->alias = empty($alias) ? null : $this->verifyAlias($alias);      
        $new->table = $this->buildTable($new->name, $new->alias);
        $new->columns = [];
        $new->columnNames = [];
        
        foreach($this->columns as $column)
        {
            $newColumn = $column->withTableAlias($new->alias);
            $new->columns[$newColumn->column()] = $newColumn;
            $new->columnNames[$newColumn->name()] = $newColumn->column();
        }
        
        return $new;
    }

    /**
     * Returns the column names.
     *
     * @return array<int, string>
     */    
    public function getColumnNames(): array
    {
        return array_keys($this->columnNames);
    }
    
    /**
     * Returns the created columns.
     *
     * @param array<int, string> $columns
     * @param null|array<int, string> $columnNames
     * @return array<string, ColumnInterface>
     */    
    protected function createColumns(array $columns, null|array $columnNames = null): array
    {
        $created = [];
        $this->columnNames = [];
        
        foreach($columns as $column)
        {
            $column = $this->columnFactory->createColumn($column);
            
            // skip if column is not from same table.
            if (
                $column->tableAlias()
                && $column->tableAlias() !== $this->alias
            ) {
                continue;
            }
            
            $column = $column->withTableAlias($this->alias);
                
            // create columns only if in column names.
            if (
                !is_null($columnNames)
                && !in_array($column->name(), $columnNames)
            ) {
                continue;
            }
            
            $created[$column->column()] = $column;
            
            $this->columnNames[$column->name()] = $column->column();
        }
        
        return $created;
    }
    
    /**
     * Parses the table.
     *
     * @param string $table The table such as 'products', 'products p', 'product as p'.
     * @return array ['table', 'alias'] or null if no table alias ['table', null]
     */    
    protected function parseTable(string $table): array
    {
        $segments = explode(' ', $table);
        
        if (count($segments) === 1)
        {
            return [$table, null];
        }
    
        if (count($segments) === 2)
        {
            return [$segments[0], $this->verifyAlias($segments[1])];
        }

        if (count($segments) === 3 && $segments[1] === 'as')
        {
            return [$segments[0], $this->verifyAlias($segments[2])];
        }
                
        return [$table, null];
    }
    
    /**
     * Returns the verified alias or null.
     *
     * @param null|string $alias The alias to verify.
     * @return null|string
     */    
    protected function verifyAlias(null|string $alias): null|string
    {
        if (is_null($alias)) {
            return null;
        }
        
        $valid = (bool) preg_match('/^[a-zA-Z]+[a-zA-Z_]*?$/', $alias);
        
        return $valid ? $alias : null;
    }
    
    /**
     * Build table
     *
     * @param string $name The name of the table.
     * @param null|string $alias The table alias or null.
     * @return string
     */    
    protected function buildTable(
        string $name,
        null|string $alias,
    ): string {
        
        if (empty($alias)) {
            return $name;
        }
        
        return implode(' ', [$name, $alias]);
    }
}