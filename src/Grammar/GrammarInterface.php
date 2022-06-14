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
 
namespace Tobento\Service\Storage\Grammar;

/**
 * Grammar
 */
interface GrammarInterface
{    
    /**
     * Get the statement
     *
     * @return null|string 'SELECT id, date_created FROM products' e.g. or null on failure
     * @throws GrammarException
     */    
    public function getStatement(): null|string;

    /**
     * Get the bindings.
     *
     * @return array
     */    
    public function getBindings(): array;   
}