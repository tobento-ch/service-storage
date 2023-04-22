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
use Tobento\Service\Storage\ItemsInterface;
use Tobento\Service\Storage\Items;
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\Item;
use Tobento\Service\Collection\Collection;

/**
 * ItemsTest
 */
class ItemsTest extends TestCase
{
    public function testThatItemIsInstanceofItemsInterface()
    {
        $this->assertInstanceof(
            ItemsInterface::class,
            new Items()
        );
    }
    
    public function testGetMethod()
    {
        $items = new Items(['key' => 'value']);
        
        $this->assertSame('value', $items->get('key'));
        $this->assertSame(null, $items->get('foo'));
        $this->assertSame('default', $items->get('foo', 'default'));
    }
    
    public function testAllMethod()
    {
        $items = new Items(['key' => 'value']);
        
        $this->assertSame(['key' => 'value'], $items->all());
    }
    
    public function testCollectionMethod()
    {
        $items = new Items(['key' => 'value']);
        
        $this->assertInstanceof(
            Collection::class,
            $items->collection()
        );
    }
    
    public function testCountMethod()
    {
        $this->assertSame(0, (new Items())->count());
        $this->assertSame(2, (new Items(['key' => 'value', 'foo' => 'Foo']))->count());
    }
    
    public function testCountMethodUsesItemsCountInsteadIfSet()
    {
        $items = new Items([['foo' => 'Foo']], itemsCount: 3);
        
        $this->assertSame(3, $items->count());
    }
    
    public function testToArrayMethod()
    {
        $items = new Items(['key' => 'value']);
        
        $this->assertSame(
            ['key' => 'value'],
            $items->toArray()
        );
    }
    
    public function testToJsonMethod()
    {
        $items = new Items(['key' => 'value']);
        
        $this->assertSame(
            '{"key":"value"}',
            $items->toJson()
        );
    }
    
    public function testActionMethod()
    {
        $item = new Items(['key' => 'value'], action: 'get');
        
        $this->assertSame('get', $item->action());
    }
    
    public function testFirstMethod()
    {
        $items = new Items([
            ['foo' => 'Foo'],
            ['bar' => 'Bar'],
        ]);
        
        $this->assertSame(['foo' => 'Foo'], $items->first());
    }
    
    public function testFirstMethodIfNoItemsReturnNull()
    {
        $items = new Items();
        
        $this->assertSame(null, $items->first());
    }
    
    public function testMapMethod()
    {
        $items = new Items([
            ['foo' => 'Foo'],
            ['bar' => 'Bar'],
        ]);
        
        $itemsNew = $items->map(function(array $item): object {
            return new Item($item);
        });
        
        $this->assertFalse($items === $itemsNew);
        
        $this->assertInstanceof(ItemInterface::class, $itemsNew->first());
    }
    
    public function testReindexMethod()
    {
        $items = new Items([
            ['sku' => 'foo', 'name' => 'Foo'],
            ['sku' => 'bar', 'name' => 'Bar'],
            ['sku' => 'foo', 'name' => 'Another'],
        ]);
        
        $itemsNew = $items->reindex(function(array $item): int|string {
            return $item['sku'];
        });
        
        $this->assertFalse($items === $itemsNew);
        $this->assertsame(['foo', 'bar'], array_keys($itemsNew->all()));
    }
}