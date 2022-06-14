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
 * ColumnFactory
 */
class ColumnFactory implements ColumnFactoryInterface
{    
    /**
     * Create a new Column.
     *
     * @param string $column
     * @return ColumnInterface
     */    
    public function createColumn(string $column): ColumnInterface
    {        
        return new Column($column);
    }
}