<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Storage\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\Result;
use Tobento\Service\Storage\ResultInterface;
use Tobento\Service\Storage\Item;
use Tobento\Service\Storage\Items;

/**
 * ResultTest
 */
class ResultTest extends TestCase
{
    public function testThatItemIsInstanceofItemInterface()
    {
        $this->assertInstanceof(
            ResultInterface::class,
            new Result('action', new Item(), new Items())
        );
    }
    
    public function testActionMethod()
    {
        $item = new Item();
        $items = new Items();
        $result = new Result('action', $item, $items);
        
        $this->assertSame('action', $result->action());
    }
    
    public function testItemAndItemsMethod()
    {
        $item = new Item();
        $items = new Items();
        $result = new Result('action', $item, $items);
        
        $this->assertSame($item, $result->item());
        $this->assertSame($items, $result->items());
    }
    
    public function testItemsCountMethod()
    {
        $item = new Item();
        $items = new Items();
        $result = new Result('action', $item, $items);
        
        $this->assertSame(0, $result->itemsCount());
        
        $result = new Result('action', $item, $items, 5);

        $this->assertSame(5, $result->itemsCount());
    }
    
    public function testItemsCountMethodGetsCountFromItemsIfNotSet()
    {
        $item = new Item();
        $items = new Items(['foo', 'bar']);
        $result = new Result('action', $item, $items);
        
        $this->assertSame(2, $result->itemsCount());
        
        $result = new Result('action', $item, $items, 5);

        $this->assertSame(5, $result->itemsCount());
    }    
}