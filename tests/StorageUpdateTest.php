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
use Tobento\Service\Storage\ItemsInterface;

/**
 * StorageUpdateTest
 */
abstract class StorageUpdateTest extends TestCase
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

    public function testItemsGetsUpdated()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'price' => 1.55],
            ['sku' => 'pencil', 'price' => 1.23],
        ]);

        $updatedItems = $this->storage
            ->table('products')
            ->update([
                'price' => 4.55,
            ]);

        $this->assertEquals(
            [
                ['sku' => 'pen', 'price' => 4.55],
                ['sku' => 'pencil', 'price' => 4.55],
            ],
            $this->storage->table('products')->select('sku', 'price')->get()->all()
        );
    }
    
    public function testItemGetsInsertedAndIgnoresInvalidColumns()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'price' => 1.55],
        ]);
        
        $updatedItems = $this->storage
            ->table('products')
            ->update([
                'price' => 4.55,
                'invalid' => 'foo',
            ]);

        $item = $this->storage->table('products')->first()->all();
        unset($item['sku']);
        unset($item['title']);
        
        $this->assertEquals(
            ['id' => 1, 'price' => 4.55],
            $item
        );
    }
    
    public function testArrayValueGetsCasted()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ]);
        
        $updatedItems = $this->storage
            ->table('products')
            ->update([
                'title' => ['de' => 'DE'],
            ]);

        $this->assertEquals(
            ['id' => 1, 'title' => '{"de":"DE"}'],
            $this->storage->table('products')->select('id', 'title')->first()->all()
        );
    }

    public function testUpdateWithEmptyItemsReturnsZeroCount()
    {
        $updatedItems = $this->storage
            ->table('products')
            ->update([]);
        
        $this->assertSame(0, $updatedItems->count());       
    }
    
    public function testReturningNullReturnsEmptyItem()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ]);
        
        $updatedItems = $this->storage
            ->table('products')
            ->update(['sku' => 'sku new'], return: null);

        $this->assertEquals(
            [],
            $updatedItems->all()
        );
    }
    
    public function testReturningSpecificColumns()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ]);
        
        $updatedItems = $this->storage
            ->table('products')
            ->update(['sku' => 'sku new'], return: ['id']);

        $this->assertEquals(
            [['id' => 1]],
            $updatedItems->all()
        );
    }
    
    public function testReturningSpecificColumnsIgnoresInvalid()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ]);
        
        $updatedItems = $this->storage
            ->table('products')
            ->update(['sku' => 'pen new'], return: ['sku', 'unknown']);

        $this->assertEquals(
            [['sku' => 'pen new']],
            $updatedItems->all()
        );
    }
    
    public function testReturningAction()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen new'],
        ]);
                
        $updatedItems = $this->storage
            ->table('products')
            ->update(['sku' => 'pen new']);

        $this->assertEquals(
            'update',
            $updatedItems->action()
        );
    }
    
    public function testUpdateOrInsertMethodItemsGetsUpdated()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'price' => 1.55],
            ['sku' => 'pencil', 'price' => 1.23],
        ]);
        
        $items = $this->storage->table('products')->updateOrInsert(
            ['id' => 2],
            ['sku' => 'glue'],
        );
        
        $this->assertInstanceOf(ItemsInterface::class, $items);

        $this->assertEquals(
            [
                ['sku' => 'pen'],
                ['sku' => 'glue'],
            ],
            $this->storage->table('products')->select('sku')->get()->all()
        );
    }
    
    public function testUpdateOrInsertMethodItemGetInsertedIfNotExist()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'price' => 1.55],
            ['sku' => 'pencil', 'price' => 1.23],
        ]);
        
        $items = $this->storage->table('products')->updateOrInsert(
            ['id' => 3],
            ['sku' => 'glue'],
        );
        
        $this->assertInstanceOf(ItemInterface::class, $items);

        $this->assertEquals(
            [
                ['sku' => 'pen'],
                ['sku' => 'pencil'],
                ['sku' => 'glue'],
            ],
            $this->storage->table('products')->select('sku')->get()->all()
        );
    }
}