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
 * TableFactory
 */
class TableFactory implements TableFactoryInterface
{
    /**
     * Create a new TableFactory.
     *
     * @param null|ColumnFactoryInterface $columnFactory
     */    
    public function __construct(
        protected null|ColumnFactoryInterface $columnFactory = null,
    ) {
        $this->columnFactory = $columnFactory ?: new ColumnFactory();
    }
    
    /**
     * Create a new Table.
     *
     * @param string $table
     * @param array<int, string> $columns
     * @param null|string $primaryKey
     * @return TableInterface
     */    
    public function createTable(
        string $table,
        array $columns = [],
        null|string $primaryKey = null
    ): TableInterface {        
        return new Table($table, $columns, $primaryKey, $this->columnFactory);
    }
}