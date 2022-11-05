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
 * StorageInsertItemsTest
 */
abstract class StorageInsertItemsTest extends TestCase
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
    
    public function testItemsGetsInserted()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'foo'],
            ['sku' => 'bar'],
        ]);

        $this->assertEquals(
            ['id' => 1, 'sku' => 'foo'],
            $this->storage->table('products')->select('id', 'sku')->find(1)->all()
        );
        
        $this->assertEquals(
            ['id' => 2, 'sku' => 'bar'],
            $this->storage->table('products')->select('id', 'sku')->find(2)->all()
        );
    }
    
    public function testItemsGetsInsertedAndIgnoresInvalidColumns()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'unknown' => 'foo'],
        ]);
        
        $item = $this->storage->table('products')->first()->all();
        unset($item['price']);
        unset($item['title']);
        
        $this->assertEquals(
            ['id' => 1, 'sku' => 'pen'],
            $item
        );
    }    
    
    public function testArrayValueGetsCasted()
    {        
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'foo', 'title' => ['de' => 'DE']],
        ]);
        
        $this->assertEquals(
            [
                ['title' => '{"de":"DE"}'],
            ],
            $this->storage->table('products')->select('title')->get()->all()
        );
    }
    
    public function testInsertWithEmptyItemReturnsWithZeroItemsCount()
    {
        $insertedItems = $this->storage->table('products')->insertItems([]);
        
        $this->assertSame(0, $insertedItems->count());
    }
    
    public function testReturningNullReturnsEmptyItems()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ], return: null);

        $this->assertEquals(
            [],
            $insertedItems->all()
        );
    }
    
    public function testReturningSpecificColumns()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ], return: ['id']);

        $this->assertEquals(
            [['id' => 1]],
            $insertedItems->all()
        );
    }
    
    public function testReturningSpecificColumnsIgnoresInvalid()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ], return: ['sku', 'unknown']);

        $this->assertEquals(
            [['sku' => 'pen']],
            $insertedItems->all()
        );
    }
    
    public function testReturningAction()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ]);

        $this->assertEquals(
            'insertItems',
            $insertedItems->action()
        );
    }
}