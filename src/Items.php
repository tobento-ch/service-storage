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

use Tobento\Service\Support\Arrayable;
use Tobento\Service\Support\Jsonable;
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Collection\Arr;
use ArrayAccess;
use Generator;

/**
 * Items
 */
class Items implements ItemsInterface, Arrayable, Jsonable
{
    use UsesItems;
    
    /**
     * Create a new Items.
     *
     * @param iterable $items The items.
     * @param null|int $itemsCount
     * @param string $action The action such as insert.
     */
    final public function __construct(
        iterable $items = [],
        protected null|int $itemsCount = null,
        protected string $action = ''
    ){
        $this->items = Iter::toArray(iterable: $items);
    }
    
    /**
     * Returns the number of items.
     *
     * @return int
     */
    public function count(): int
    {
        if (!is_null($this->itemsCount)) {
            return $this->itemsCount;
        }
        
        return count($this->items);
    }
    
    /**
     * Returns the action name.
     *
     * @return string
     */    
    public function action(): string
    {
        return $this->action;
    }
    
    /**
     * Returns the first item.
     *
     * @return null|array|object
     */    
    public function first(): null|array|object
    {
        $key = array_key_first($this->items);
        
        if (is_null($key)) {
            return null;
        }
        
        return $this->items[$key];
    }
    
    /**
     * Returns the column of the items.
     *
     * @param string $column
     * @param null|string $index
     * @return array
     */
    public function column(string $column, null|string $index = null): array
    {
        return array_column($this->items, $column, $index);
    }

    /**
     * Returns a new instance with the mapped items.
     *
     * @param callable $mapper
     * @return static
     */
    public function map(callable $mapper): static
    {
        $generator = (static function(iterable $items) use ($mapper): Generator {
            foreach($items as $key => $item) {
                yield $key => $mapper($item);
            }
        })($this->items);

        return new static($generator, $this->itemsCount, $this->action);
    }
    
    /**
     * Returns a new instance with the reindexed items.
     *
     * @param callable $indexer
     * @return static
     */
    public function reindex(callable $indexer): static
    {
        $generator = (static function(iterable $items) use ($indexer): Generator {
            foreach($items as $item) {
                yield $indexer($item) => $item;
            }
        })($this->items);

        return new static($generator, $this->itemsCount, $this->action);
    }
    
    /**
     * Returns a new instance with the groupBy items.
     *
     * @param string|callable $groupBy
     * @param null|callable $groupAs
     * @param bool $preserveKeys
     * @return static
     */
    public function groupBy(string|callable $groupBy, null|callable $groupAs = null, bool $preserveKeys = true): static
    {
        $groups = Arr::groupBy($this->items, $groupBy, $groupAs, $preserveKeys);
        
        return new static($groups, null, $this->action);
    }
}