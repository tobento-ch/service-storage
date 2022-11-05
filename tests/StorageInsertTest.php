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
 * StorageInsertTest
 */
abstract class StorageInsertTest extends TestCase
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

    public function testItemGetsInserted()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
        ]);

        $this->assertEquals(
            ['id' => 1, 'sku' => 'pen'],
            $this->storage->table('products')->select('id', 'sku')->first()->all()
        );
    }
    
    public function testItemGetsInsertedAndIgnoresInvalidColumns()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
            'unknown' => 'foo',
        ]);

        $this->assertEquals(
            ['id' => 1, 'sku' => 'pen'],
            $this->storage->table('products')->select('id', 'sku')->first()->all()
        );
    }
    
    public function testArrayValueGetsCasted()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
            'title' => ['de' => 'DE'],
        ]);

        $this->assertEquals(
            ['id' => 1, 'title' => '{"de":"DE"}'],
            $this->storage->table('products')->select('id', 'title')->first()->all()
        );
    }

    public function testInsertWithEmptyItemsReturnsNull()
    {
        $insertedItem = $this->storage->table('products')->insert([]);
        
        $this->assertNull($insertedItem);       
    }
    
    public function testReturningNullReturnsEmptyItem()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
        ], return: null);

        $this->assertEquals(
            [],
            $insertedItem->all()
        );
    }
    
    public function testReturningSpecificColumns()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
        ], return: ['id']);

        $this->assertEquals(
            ['id' => 1],
            $insertedItem->all()
        );
    }
    
    public function testReturningSpecificColumnsIgnoresInvalid()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
        ], return: ['sku', 'unknown']);

        $this->assertEquals(
            ['sku' => 'pen'],
            $insertedItem->all()
        );
    }
    
    public function testReturningAction()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'pen',
        ]);

        $this->assertEquals(
            'insert',
            $insertedItem->action()
        );
    }
}