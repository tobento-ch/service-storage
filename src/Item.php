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

/**
 * Item
 */
final class Item implements ItemInterface, Arrayable, Jsonable
{
    use UsesItems;
    
    /**
     * Create a new Items.
     *
     * @param iterable $items The items.
     */
    final public function __construct(
        iterable $items = []
    ){
        $this->items = $this->iterableToArray($items);
    }
}