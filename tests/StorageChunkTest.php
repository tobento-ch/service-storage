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

/**
 * StorageChunkTest
 */
abstract class StorageChunkTest extends TestCase
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

    public function testColumnMethod()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'title' => 'Pen'],
            ['sku' => 'pencil', 'title' => 'Pencil'],
        ]);

        $column = $this->storage->table('products')
            ->chunk(length: 2000)
            ->column('sku');
        
        $items = [];
        
        foreach($column as $key => $value) {
            $items[$key] = $value;
        }
        
        $this->assertEquals(
            [
                0 => 'pen',
                1 => 'pencil',
            ],
            $items
        );
    }
    
    public function testColumnMethodWithIndex()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'title' => 'Pen'],
            ['sku' => 'pencil', 'title' => 'Pencil'],
        ]);

        $column = $this->storage->table('products')
            ->chunk(length: 2000)
            ->column('title', 'sku');
        
        $items = [];
        
        foreach($column as $key => $value) {
            $items[$key] = $value;
        }
        
        $this->assertEquals(
            [
                'pen' => 'Pen',
                'pencil' => 'Pencil',
            ],
            $items
        );
    }
    
    public function testGetMethod()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'title' => 'Pen'],
            ['sku' => 'pencil', 'title' => 'Pencil'],
        ]);

        $queryItems = $this->storage->table('products')
            ->chunk(length: 2000)
            ->select('sku', 'title')
            ->get();
        
        $items = [];
        
        foreach($queryItems as $key => $item) {
            $items[$key] = $item;
        }
        
        $this->assertEquals(
            [
                ['sku' => 'pen', 'title' => 'Pen'],
                ['sku' => 'pencil', 'title' => 'Pencil'],
            ],
            $items
        );
    }
    
    public function testInsertItemsMethod()
    {
        $insertedItems = $this->storage
            ->table('products')
            ->chunk(length: 10000)
            ->insertItems([
                ['sku' => 'pen', 'title' => 'Pen'],
                ['sku' => 'pencil', 'title' => 'Pencil'],
            ], return: ['id', 'sku']);
        
        $items = [];
        
        foreach($insertedItems as $key => $item) {
            $items[$key] = $item;
        }
        
        $this->assertEquals(
            [
                ['id' => 1, 'sku' => 'pen'],
                ['id' => 2, 'sku' => 'pencil'],
            ],
            $items
        );
    }
    
    public function testInsertItemsMethodWithReturnNull()
    {
        $insertedItems = $this->storage
            ->table('products')
            ->chunk(length: 10000)
            ->insertItems([
                ['sku' => 'glue', 'title' => 'Pen'],
                ['sku' => 'pencil', 'title' => 'Pencil'],
            ], return: null);
        
        $this->assertSame([], $insertedItems->all());
        
        $this->assertSame(2, $insertedItems->count());
    }
}