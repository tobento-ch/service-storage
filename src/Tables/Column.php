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
 * Column
 */
class Column implements ColumnInterface
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
     * @var null|string
     */    
    protected null|string $tableAlias;    
    
    /**
     * @var null|array<int, string>
     */    
    protected null|array $jsonSegments = null;
    
    /**
     * Create a new Column.
     *
     * @param string $column
     */    
    public function __construct(
        protected string $column,
    ) {
        [$this->name, $this->tableAlias, $this->alias, $this->jsonSegments] = $this->parseColumn($column);
    }

    /**
     * Returns the column.
     *
     * @return string
     */    
    public function column(): string
    {
        return $this->column;
    }
    
    /**
     * Returns the table alias if any.
     *
     * @return null|string
     */    
    public function tableAlias(): null|string
    {
        return $this->tableAlias;
    }
    
    /**
     * Returns a new instance with the specified table alias if any.
     *
     * @param null|string $alias
     * @return static
     */    
    public function withTableAlias(null|string $alias): static
    {
        $column = $this->buildColumn(
            $this->name,
            empty($alias) ? null : $this->verifyAlias($alias),
            $this->alias,
            $this->jsonSegments
        );
        
        return new static($column);
    }
    
    /**
     * Returns the name of the column without alias and json.
     *
     * @return string
     */    
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the alias of the column if any.
     *
     * @return null|string
     */    
    public function alias(): null|string
    {
        return $this->alias;
    }
    
    /**
     * Returns a new instance with the specified column alias if any.
     *
     * @param null|string $alias
     * @return static
     */    
    public function withAlias(null|string $alias): static
    {
        $column = $this->buildColumn(
            $this->name,
            $this->tableAlias,
            empty($alias) ? null : $this->verifyAlias($alias),
            $this->jsonSegments
        );
        
        return new static($column);
    }
        
    /**
     * Returns the json segments if any
     *
     * @return null|array
     */    
    public function jsonSegments(): null|array
    {
        return $this->jsonSegments;
    }
    
    /**
     * Returns a new instance with the specified json segments if any.
     *
     * @param null|array $segments
     * @return static
     */    
    public function withJsonSegments(null|array $segments): static
    {
        $column = $this->buildColumn(
            $this->name,
            $this->tableAlias,
            $this->alias,
            $segments
        );
        
        return new static($column);
    }
        
    /**
     * Parses the column.
     *
     * @param string $column The column such as 'title', 'table.title', 'table.title alias', 'table.data->foo as alias'.
     * @return array ['colname', 'tableAlias', 'alias', ['segment']] or null if no table alias ['colname', null, null, null]
     */    
    protected function parseColumn(string $column): array
    {
        $segments = explode('.', $column, 2);
        
        if (count($segments) > 1)
        {
            [$column, $alias] = $this->normalizeAlias($segments[1]);
            
            [$column, $jsonPath] = $this->normalizeJsonPath($column);
            
            return [$column, $this->verifyAlias($segments[0]), $alias, $jsonPath];
        }
        
        [$column, $alias] = $this->normalizeAlias($column);
        
        [$column, $jsonPath] = $this->normalizeJsonPath($column);
        
        return [$column, null, $alias, $jsonPath];
    }
    
    /**
     * Normalize json path from column.
     *
     * @param string $column The column such as 'title', 'data->options'.
     * @return array ['colname', ['segment']] or null if no json path ['colname', null]
     */    
    protected function normalizeJsonPath(string $column): array
    {
        if (!str_contains($column, '->'))
        {
            return [$column, null];
        }
        
        $segments = explode('->', $column);
        $column = $segments[0];
        array_shift($segments);
        
        $path = implode('', $segments);
        
        $valid = (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $path);
        
        if (!$valid)
        {
            $segments = null;
        }
        
        return [$column, $segments];
    }
    
    /**
     * Normalize an aliased string.
     *
     * @param string $string Any value
     * @return array ['colname', 'alias'] or null if no table alias ['colname', null]
     */    
    protected function normalizeAlias(string $string): array
    {
        $segments = explode(' ', $string);
        
        if (count($segments) === 1)
        {
            return [$string, null];
        }
    
        if (count($segments) === 2)
        {
            return [$segments[0], $this->verifyAlias($segments[1])];
        }

        if (count($segments) === 3 && $segments[1] === 'as')
        {
            return [$segments[0], $this->verifyAlias($segments[2])];
        }
                
        return [$string, null];
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
     * Build column
     *
     * @param string $column The column such as 'title'.
     * @param null|string $tableprefix The column table prefix/alias.
     * @param null|string $alias The column alias.
     * @param null|array $jsonSegments
     * @return string
     */    
    protected function buildColumn(
        string $column,
        null|string $tableprefix,
        null|string $alias,
        null|array $jsonSegments = null
    ): string {
        
        if (!is_null($jsonSegments))
        {
            $column = $column.'->'.implode('->', $jsonSegments);
        }
        
        if (!empty($alias))
        {
            $column = $column.' as '.$alias;
        }
        
        if (empty($tableprefix))
        {
            return $column;
        }
        
        return implode('.', [$tableprefix, $column]);
    }
}