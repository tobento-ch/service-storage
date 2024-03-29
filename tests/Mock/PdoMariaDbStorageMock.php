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
 
namespace Tobento\Service\Storage\Test\Mock;

use Tobento\Service\Storage\PdoMariaDbStorage;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\Tables\TablesInterface;

/**
 * PdoMariaDbStorageMock
 */
class PdoMariaDbStorageMock extends PdoMariaDbStorage
{        
    /**
     * Create a new PdoStorage
     *
     * @param null|TablesInterface $tables
     */    
    public function __construct(
        null|TablesInterface $tables = null,
    ) {        
        $this->tables = $tables ?: new Tables();
    } 
}