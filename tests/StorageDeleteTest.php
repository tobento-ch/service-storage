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
use Tobento\Service\Database\Schema\Table;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\StorageException;
use Tobento\Service\Storage\Grammar\GrammarException;
use Tobento\Service\Storage\ItemInterface;

/**
 * StorageDeleteTest
 */
abstract class StorageDeleteTest extends TestCase
{
    protected null|StorageInterface $storage = null;
    protected null|TablesInterface $tables = null;    
    protected null|Table $tableProducts = null;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id');
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('sku', 100)->nullable(false)->default('');
        $tableProducts->decimal('price', 15, 2);
        $tableProducts->string('title')->nullable(false)->default('');
        $this->tableProducts = $tableProducts;
    }

    public function testDeleteSingleWithWhereId()
    {
        $this->storage->table('products')->insertItems([
            ['sku' => 'foo'],
            ['sku' => 'bar'],
        ]);
        
        $this->assertInstanceOf(
            ItemInterface::class,
            $this->storage->table('products')->find(2)
        );
        
        $result = $this->storage->table('products')->where('id', '=', 2)->delete();
        
        $this->assertSame(null, $this->storage->table('products')->find(2));
    }
    
    public function testDeleteMultiple()
    {
        $this->storage->table('products')->insertItems([
            ['sku' => 'foo'],
            ['sku' => 'bar'],
        ]);
        
        $this->assertSame(2, $this->storage->table('products')->count());
        
        $deletedItems = $this->storage->table('products')->delete();
        
        $this->assertSame(0, $this->storage->table('products')->count());
    }
    
    public function testDeleteIfNoDeletionReturnsZeroCount()
    {
        $deletedItems = $this->storage->table('products')->where('id', '=', 999)->delete();
        
        $this->assertSame(0, $deletedItems->count());
    }
    
    public function testReturningNullReturnsEmptyItems()
    {
        $this->storage->table('products')->insert(['sku' => 'foo']);
        
        $deletedItems = $this->storage->table('products')->delete(return: null);

        $this->assertEquals(
            [],
            $deletedItems->all()
        );
    }
    
    public function testReturningAllColumnsIfNotSpecified()
    {
        $this->storage->table('products')->insert(['sku' => 'foo']);
        
        $deletedItems = $this->storage->table('products')->delete();
        
        $items = $deletedItems->all();
        
        foreach($items as $key => $item) {
            unset($items[$key]['price']);
            unset($items[$key]['title']);            
        }
        
        $this->assertEquals(
            [['id' => 1, 'sku' => 'foo']],
            $items
        );
    }
    
    public function testReturningSpecificColumns()
    {
        $this->storage->table('products')->insert(['sku' => 'foo']);
        
        $deletedItems = $this->storage->table('products')->delete(return: ['id']);

        $this->assertEquals(
            [['id' => 1]],
            $deletedItems->all()
        );
    }
    
    public function testReturningSpecificColumnsIgnoresInvalid()
    {
        $this->storage->table('products')->insert(['sku' => 'foo']);
        
        $deletedItems = $this->storage->table('products')->delete(return: ['id', 'unknown']);

        $this->assertEquals(
            [['id' => 1]],
            $deletedItems->all()
        );
    }
    
    public function testReturningAction()
    {
        $deletedItems = $this->storage->table('products')->delete();

        $this->assertEquals(
            'delete',
            $deletedItems->action()
        );
    }
}