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
 
namespace Tobento\Service\Storage;

use Tobento\Service\Collection\Collection;
use ArrayAccess;
use IteratorAggregate;
use Countable;

/**
 * ItemInterface
 */
interface ItemInterface extends ArrayAccess, Countable, IteratorAggregate
{
    /**
     * Get an item value by key.
     *
     * @param string|int $key The key.
     * @param mixed $default A default value.
     * @return mixed The the default value if not exist.
     */
    public function get(string|int $key, mixed $default = null): mixed;
    
    /**
     * Returns all items.
     *
     * @return array
     */
    public function all(): array;
    
    /**
     * Returns a new Collection with the items.
     *
     * @return Collection
     */
    public function collection(): Collection;
    
    /**
     * Returns the action name.
     *
     * @return string
     */    
    public function action(): string;
}