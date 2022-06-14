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
 * SubQuery
 */
class SubQuery
{    
    /**
     * Create a new SubQuery
     *
     * @param string $statement
     * @param array $bindings
     */    
    public function __construct(
        protected string $statement,
        protected array $bindings = []
    ) {}

    /**
     * Get the statement.
     *
     * @return string
     */    
    public function statement(): string
    {
        return $this->statement;
    }

    /**
     * Get the bindings.
     *
     * @return array
     */    
    public function bindings(): array
    {
        return $this->bindings;
    }
}