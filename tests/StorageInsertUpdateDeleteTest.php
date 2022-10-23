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
use Tobento\Service\Database\Schema\Items;
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\StorageException;
use Tobento\Service\Storage\StorageResult;
use Tobento\Service\Storage\Grammar\GrammarException;

/**
 * StorageInsertUpdateDeleteTest
 */
abstract class StorageInsertUpdateDeleteTest extends TestCase
{
    protected null|StorageInterface $storage = null;
    protected null|TablesInterface $tables = null;
    
    protected array $products = [];
    protected array $productsLg = [];
    
    protected null|Table $tableProducts = null;
    protected null|Table $tableProductsLg = null;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description']);
        
        $this->products = [
            1 => ['id' => 1, 'sku' => 'paper', 'price' => '12.00', 'title' => 'Blatt'],
            2 => ['id' => 2, 'sku' => 'pen', 'price' => '1.56', 'title' => ''],
            3 => ['id' => 3, 'sku' => 'glue', 'price' => '33.05', 'title' => ''],
            4 => ['id' => 4, 'sku' => 'pencil', 'price' => null, 'title' => ''],
            5 => ['id' => 5, 'sku' => 'scissors', 'price' => null, 'title' => ''],
            6 => ['id' => 6, 'sku' => 'brush', 'price' => null, 'title' => ''],
        ];
        
        $this->productsLg = [
            ['product_id' => 1, 'language_id' => 1, 'title' => 'Papier', 'description' => '180mg Papier'],
            ['product_id' => 1, 'language_id' => 2, 'title' => 'Paper', 'description' => '180mg paper'],
            ['product_id' => 2, 'language_id' => 1, 'title' => 'Stift', 'description' => 'Wasserfester Stift'],
            ['product_id' => 2, 'language_id' => 2, 'title' => 'Pen', 'description' => ''],
            ['product_id' => 3, 'language_id' => 1, 'title' => 'Leim', 'description' => ''],
            ['product_id' => 3, 'language_id' => 2, 'title' => 'Glue', 'description' => ''],
        ];        
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('sku', 100)->nullable(false)->default('');
        $tableProducts->decimal('price', 15, 2);
        $tableProducts->string('title')->nullable(false)->default('');
        $tableProducts->items(new Items($this->products));
        $this->tableProducts = $tableProducts;
        
        $tableProductsLg = new Table(name: 'products_lg');
        $tableProductsLg->bigInt('product_id');
        $tableProductsLg->int('language_id');
        $tableProductsLg->string('title')->nullable(false)->default('');
        $tableProductsLg->text('description');
        $tableProductsLg->items(new Items($this->productsLg));
        $this->tableProductsLg = $tableProductsLg;
        
        /*$this->storage = new InMemoryStorage([
            'products' => $this->products,
            'products_lg' => $this->productsLg,    
        ], $tables);*/
    }

    public function tearDown(): void
    {
        //$this->storage = null;
    }

    public function testInsertWithoutPrimaryKeyReturnsCreatedKey()
    {        
        $result = $this->storage->table('products')->insert([
            'sku' => 'foo',
            'price' => '9.99',
        ]);
            
        $this->assertEquals(
            [
                'sku' => 'foo',
                'price' => '9.99',
                'id' => 7,
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'insert',
            $result->action()
        );
        
        $result = $this->storage->table('products')->insert([
            'sku' => 'bar',
            'price' => '19.99',
        ]);
            
        $this->assertEquals(
            [
                'sku' => 'bar',
                'price' => '19.99',
                'id' => 8,
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'insert',
            $result->action()
        );        
    }
    
    public function testInsertWithArrayValueGetsCasted()
    {        
        $result = $this->storage->table('products')->insert([
            'sku' => 'foo',
            'price' => '9.99',
            'title' => ['de' => 'DE'],
        ]);
            
        $this->assertEquals(
            [
                'sku' => 'foo',
                'price' => '9.99',
                'title' => ['de' => 'DE'],
                'id' => 7,
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            [
                'sku' => 'foo',
                'price' => '9.99',
                'id' => 7,
                'title' => '{"de":"DE"}',
            ],
            $this->storage->table('products')->find(7)?->all()
        );
    }
    
    public function testInsertTableWithoutPrimary()
    {
        $result = $this->storage->table('products_lg')->insert([
            'product_id' => 4,
            'language_id' => 1,
            'title' => 'Pencil',
            'description' => 'Pencil Desc',
        ]);
            
        $this->assertEquals(
            [
                'product_id' => 4,
                'language_id' => 1,
                'title' => 'Pencil',
                'description' => 'Pencil Desc',
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'insert',
            $result->action()
        );       
    }
    
    public function testInsertWithEmptyItemReturnsNull()
    {
        $result = $this->storage->table('products')->insert([]);
        
        $this->assertNull($result);       
    }
    
    public function testUpdateSingleWithWhereId()
    {
        $result = $this->storage->table('products')->where('id', '=', 3)->update([
            'sku' => 'glue new',
            'price' => '30.05',
        ]);
            
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '30.05',
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'update',
            $result->action()
        );
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '30.05',
                'id' => 3,
                'title' => '',
            ],
            $this->storage->table('products')->find(3)?->all()
        );
        
        $this->assertSame(1, $result->itemsCount());
    }

    public function testUpdateMultipleWithWhereIdGreater()
    {
        $result = $this->storage->table('products')->where('id', '>', 3)->update([
            'price' => '10.15',
        ]);
            
        $this->assertEquals(
            [
                'price' => '10.15',
            ],
            $result->item()->all()
        );
            
        $this->assertEquals(
            [
                0 => $this->storage->table('products')->find(4)?->all(),
                1 => $this->storage->table('products')->find(5)?->all(),
                2 => $this->storage->table('products')->find(6)?->all(),
            ],
            array_values($result->items()->all())
        );
        
        $this->assertEquals(
            'update',
            $result->action()
        );
        
        $this->assertSame(3, $result->itemsCount());
    }
    
    public function testUpdateWithArrayValueGetsCasted()
    {
        $result = $this->storage->table('products')->where('id', '=', 3)->update([
            'price' => '10.15',
            'title' => ['de' => 'DE'],
        ]);
            
        $this->assertEquals(
            [
                'price' => '10.15',
                'title' => ['de' => 'DE'],
            ],
            $result->item()->all()
        );
            
        $this->assertEquals(
            [
                'sku' => 'glue',
                'price' => '10.15',
                'id' => 3,
                'title' => '{"de":"DE"}',
            ],
            $this->storage->table('products')->find(3)?->all()
        );
    }
    
    public function testUpdateWithEmptyItemReturnsNull()
    {
        $result = $this->storage->table('products')->update([]);
        
        $this->assertNull($result);       
    }    
 
    public function testUpdateOrInsertWithWhereIdExistsUpdates()
    {
        $result = $this->storage->table('products')->updateOrInsert(
            [
                'id' => 3,  
            ],
            [
                'sku' => 'glue new',
                'price' => '30.05',
            ]
        );
            
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '30.05',
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'update',
            $result->action()
        );
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '30.05',
                'id' => 3,
                'title' => '',
            ],
            $this->storage->table('products')->find(3)?->all()
        );
        
        $this->assertSame(1, $result->itemsCount());
    }

    public function testUpdateOrInsertWithWhereIdDoesNotExistsInserts()
    {
        $result = $this->storage->table('products')->updateOrInsert(
            [
                'id' => 7,  
            ],
            [
                'sku' => 'glue new',
                'price' => '30.05',
            ]
        );
            
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '30.05',
                'id' => 7,
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'insert',
            $result->action()
        );
        
        $item = $this->storage->table('products')->find(7)?->all();
        unset($item['title']);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '30.05',
                'id' => 7,
            ],
            $item
        );
    }
    
    public function testUpdateOrInsertWithoutPrimaryKeyWithWhereIdExistsUpdates()
    {
        $result = $this->storage->table('products_lg')->updateOrInsert(
            [
                'language_id' => 1,  
            ],
            [
                'description' => 'NEW',
            ]
        );
            
        $this->assertEquals(
            [
                'description' => 'NEW',
                'language_id' => 1,
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'update',
            $result->action()
        );
        
        $this->assertEquals(
            array_values($this->storage->table('products_lg')->where('language_id', '=', 1)->get()->all()),
            array_values($result->items()->all())
        );
        
        $this->assertSame(3, $result->itemsCount());
    }
 
    public function testUpdateOrInsertWithoutPrimaryKeyWithWhereIdDoesNotExistsInserts()
    {
        $result = $this->storage->table('products_lg')->updateOrInsert(
            [
                'language_id' => 3,
            ],
            [
                'description' => 'NEW',
            ]
        );
            
        $this->assertEquals(
            [
                'description' => 'NEW',
                'language_id' => 3,
            ],
            $result->item()->all()
        );
        
        $this->assertEquals(
            'insert',
            $result->action()
        );
        
        $item = $this->storage->table('products_lg')->where('language_id', '=', 3)->first()?->all();
        unset($item['product_id']);
        unset($item['title']);
        
        $this->assertEquals(
            [
                'description' => 'NEW',
                'language_id' => 3,
            ],
            $item,
        );
    }
    
    public function testUpdateOrInsertWithEmptyItemReturnsNull()
    {
        $result = $this->storage->table('products')->updateOrInsert(
            [
                'id' => 3,
            ],
            [
                //'sku' => 'NEW',
            ]
        );
        
        $this->assertNull($result);       
    }    
    
    public function testDeleteSingleWithWhereId()
    {
        $result = $this->storage->table('products')->where('id', '=', 3)->delete();

        $this->assertEquals(
            [
                0 => $this->products[3],
            ],
            array_values($result->items()->all())
        );
        
        $this->assertEquals(
            'delete',
            $result->action()
        );
        
        $this->assertSame(1, $result->itemsCount());
    }
    
    public function testDeleteMulipleWithWhereIdGreater()
    {
        $result = $this->storage->table('products')->where('id', '>', 3)->delete();

        $this->assertEquals(
            [
                0 => $this->products[4],
                1 => $this->products[5],
                2 => $this->products[6],
            ],
            array_values($result->items()->all())
        );
        
        $this->assertEquals(
            'delete',
            $result->action()
        );
        
        $this->assertSame(3, $result->itemsCount());
    }
    
    public function testDeleteIfNoDeletionReturnsResultInterface()
    {
        $result = $this->storage->table('products')->where('id', '=', 999)->delete();
        
        $this->assertSame(0, $result->itemsCount());
    }    
}