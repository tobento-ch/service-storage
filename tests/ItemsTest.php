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
use Tobento\Service\Storage\Items;
use Tobento\Service\Storage\ItemsInterface;
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
}