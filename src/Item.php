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
     * @param string $action The action such as insert.
     */
    final public function __construct(
        iterable $items = [],
        protected string $action = ''
    ){
        $this->items = Iter::toArray(iterable: $items);
    }
    
    /**
     * Returns a new instance with the specified action
     *
     * @param string $action The action such as insert.
     * @return mixed The the default value if not exist.
     */
    public function withAction(string $action): static
    {
        $new = clone $this;
        $new->action = $action;
        return $new;
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
}