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
use Tobento\Service\Collection\Arr;
use Tobento\Service\Support\Arrayable;
use Tobento\Service\Support\Jsonable;
use Generator;

/**
 * UsesItems
 */
trait UsesItems
{
    /**
     * @var array The items.
     */    
    protected array $items = [];
    
    /**
     * Get an item value by key.
     *
     * @param string|int $key The key.
     * @param mixed $default A default value.
     * @return mixed The the default value if not exist.
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }
    
    /**
     * Returns all items.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Returns a new Collection with the items.
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        return new Collection($this->items);
    }
    
    /**
     * Returns an iterator for the items.
     *
     * @return Generator
     */
    public function getIterator(): Generator
    {
        foreach($this->items as $key => $item) {
            yield $key => $item;
        }
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->collection()->toArray();
    }
    
    /**
     * Object to json.
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return $this->collection()->toJson();
    }    

    /**
     * Returns the number of items.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
    
    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
    
    /**
     * iterableToArray.
     * 
     * @param iterable $items
     * @return array
     */
    protected function iterableToArray(iterable $items): array
    {
        if (is_array($items)) {
            return $items;
        }
        
        return iterator_to_array($items);
    }    
}