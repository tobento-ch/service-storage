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
 
namespace Tobento\Service\Storage\Query;

use Tobento\Service\Storage\StorageInterface;
use Closure;

/**
 * SubQueryWhere
 */
class SubQueryWhere
{    
    /**
     * Create a new SubQueryWhere
     *
     * @param Closure $callback
     * @param StorageInterface $storage
     */    
    public function __construct(
        protected Closure $callback,
        protected StorageInterface $storage
    ) {        
        // Unset table, so if in callback a new table is set, we know it is a full subquery.
        $this->storage->table('');
        $this->storage->clear();
    }

    /**
     * Get the callback.
     *
     * @return Closure
     */    
    public function callback(): Closure
    {
        return $this->callback;
    }

    /**
     * Get the storage.
     *
     * @return StorageInterface
     */    
    public function storage(): StorageInterface
    {
        return $this->storage;
    }
}