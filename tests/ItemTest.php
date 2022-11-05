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
use Tobento\Service\Storage\Item;
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Collection\Collection;

/**
 * ItemTest
 */
class ItemTest extends TestCase
{
    public function testThatItemIsInstanceofItemInterface()
    {
        $this->assertInstanceof(
            ItemInterface::class,
            new Item()
        );
    }
    
    public function testGetMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame('value', $item->get('key'));
        $this->assertSame(null, $item->get('foo'));
        $this->assertSame('default', $item->get('foo', 'default'));
    }
    
    public function testAllMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame(['key' => 'value'], $item->all());
    }
    
    public function testCollectionMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertInstanceof(
            Collection::class,
            $item->collection()
        );
    }
    
    public function testCountMethod()
    {
        $this->assertSame(0, (new Item())->count());
        $this->assertSame(2, (new Item(['key' => 'value', 'foo' => 'Foo']))->count());
    }
    
    public function testToArrayMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame(
            ['key' => 'value'],
            $item->toArray()
        );
    }
    
    public function testToJsonMethod()
    {
        $item = new Item(['key' => 'value']);
        
        $this->assertSame(
            '{"key":"value"}',
            $item->toJson()
        );
    }
    
    public function testActionMethod()
    {
        $item = new Item(['key' => 'value'], action: 'first');
        
        $this->assertSame('first', $item->action());
    }
}