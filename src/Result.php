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

/**
 * Result
 */
class Result implements ResultInterface
{
    /**
     * Create a new Result.
     *
     * @param string $action The action such as insert.
     * @param ItemInterface $item The item.
     * @param ItemsInterface $items The items.
     * @param null|int $itemsCount The items count.
     */    
    public function __construct(
        protected string $action,
        protected ItemInterface $item,
        protected ItemsInterface $items,
        protected null|int $itemsCount = null
    ) {}
    
    /**
     * Get the action name.
     *
     * @return string
     */    
    public function action(): string
    {
        return $this->action;
    }
    
    /**
     * Get the item.
     *
     * @return ItemInterface
     */
    public function item(): ItemInterface
    {
        return $this->item;
    }
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function items(): ItemsInterface
    {
        return $this->items;
    }

    /**
     * Get the items count.
     *
     * @return int
     */
    public function itemsCount(): int
    {
        if (is_null($this->itemsCount)) {
            $this->itemsCount = $this->items()->count();
        }
        
        return $this->itemsCount;
    }
}