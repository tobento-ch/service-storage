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
 * TableFactoryInterface
 */
interface TableFactoryInterface
{
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
    ): TableInterface;
}