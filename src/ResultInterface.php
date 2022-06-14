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
 * ResultInterface
 */
interface ResultInterface
{
    /**
     * Get the action name.
     *
     * @return string
     */    
    public function action(): string;
    
    /**
     * Get the item.
     *
     * @return ItemInterface
     */
    public function item(): ItemInterface;
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function items(): ItemsInterface;

    /**
     * Get the items count.
     *
     * @return int
     */    
    public function itemsCount(): int;
}