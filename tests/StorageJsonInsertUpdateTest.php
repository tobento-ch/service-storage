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
 * StorageJsonInsertUpdateTest
 */
abstract class StorageJsonInsertUpdateTest extends TestCase
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
                ->add('products', ['id', 'sku', 'price', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description', 'options']);
        
        $this->products = [
            1 => ['id' => 1, 'sku' => 'paper', 'price' => '12.00', 'title' => 'Blatt', 'data' => ''],
            2 => ['id' => 2, 'sku' => 'pen', 'price' => '1.56', 'title' => '', 'data' => ''],
            3 => ['id' => 3, 'sku' => 'glue', 'price' => '33.05', 'title' => '', 'data' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            4 => ['id' => 4, 'sku' => 'pencil', 'price' => null, 'title' => '', 'data' => ''],
            5 => ['id' => 5, 'sku' => 'scissors', 'price' => null, 'title' => '', 'data' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            6 => ['id' => 6, 'sku' => 'brush', 'price' => null, 'title' => '', 'data' => '{"color":"red","colors":["blue"],"foo": null,"options":{"language":"de"}, "numbers": [4, "6"]}'],
        ];
                
        $this->productsLg = [
            ['product_id' => 1, 'language_id' => 1, 'title' => 'Papier', 'description' => '180mg Papier', 'options' => ''],
            ['product_id' => 1, 'language_id' => 2, 'title' => 'Paper', 'description' => '180mg paper', 'options' => ''],
            ['product_id' => 2, 'language_id' => 1, 'title' => 'Stift', 'description' => 'Wasserfester Stift', 'options' => ''],
            ['product_id' => 2, 'language_id' => 2, 'title' => 'Pen', 'description' => '', 'options' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            ['product_id' => 3, 'language_id' => 1, 'title' => 'Leim', 'description' => '', 'options' => ''],
            ['product_id' => 3, 'language_id' => 2, 'title' => 'Glue', 'description' => '', 'options' => ''],
        ];        
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('sku', 100)->nullable(false)->default('');
        $tableProducts->decimal('price', 15, 2);
        $tableProducts->string('title')->nullable(false)->default('');
        $tableProducts->json('data')->nullable(false)->default('');
        $tableProducts->items($this->products);
        $this->tableProducts = $tableProducts;
        
        $tableProductsLg = new Table(name: 'products_lg');
        $tableProductsLg->bigInt('product_id');
        $tableProductsLg->int('language_id');
        $tableProductsLg->string('title')->nullable(false)->default('');
        $tableProductsLg->text('description');
        $tableProductsLg->json('options');
        $tableProductsLg->items($this->productsLg);
        $this->tableProductsLg = $tableProductsLg;
    }

    public function tearDown(): void
    {
        //
    }
    
    public function testInsertWithColumnsSpecifiedTwice()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'glue new',
            'data->foo' => 'Foo',
            'data->bar' => 'Bar',
        ], return: ['id', 'sku', 'data']);
            
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'data' => 'Bar',
                'id' => 7,
            ],
            $insertedItem->all()
        );
        
        $this->assertEquals(
            'insert',
            $insertedItem->action()
        );
        
        $item = $this->storage->table('products')->find(7)?->all();
        unset($item['title']);
        unset($item['price']);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'id' => 7,
                'data' => 'Bar',
            ],
            $item
        );
    }
    
    public function testInsertWithArrayValue()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'glue new',
            'data->foo' => ['Foo'],
        ], return: ['id', 'sku']);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                //'data' => '["Foo"]', // ['Foo'],
                'id' => 7,
            ],
            $insertedItem->all()
        );
        
        $this->assertEquals(
            'insert',
            $insertedItem->action()
        );
        
        $item = $this->storage->table('products')->find(7)?->all();
        unset($item['title']);
        unset($item['price']);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'id' => 7,
                'data' => '["Foo"]',
            ],
            $item
        );
    }    

    public function testUpdate()
    {
        $updatedItems = $this->storage->table('products')->where('id', '=', 3)->update([
            'sku' => 'glue new',
            'data->foo' => 'Foo',
        ]);
            
        /*$this->assertEquals(
            [
                'id' => 2,
                'sku' => 'glue new',
                'data' => '{"color":"blue","colors":["blue","red"],"foo":"Foo","options":{"language":"en"}}',
                'price' => '33.05',
                'title' => '',                
            ],
            $updatedItems->first()
        );*/
        
        $this->assertEquals(
            'update',
            $updatedItems->action()
        );
        
        $item = $this->storage->table('products')->find(3)?->all();;
        $item['data'] = json_decode($item['data'], true);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '33.05',
                'id' => 3,
                'title' => '',
                'data' => json_decode(
                    '{"color": "blue", "colors": ["blue", "red"], "foo":"Foo", "options": {"language": "en"}}',
                    true
                ),
            ],
            $item
        );

        $this->assertSame(1, $updatedItems->count());
    }
    
    /*
    // Cannot test as database does not support json cast type.
    public function testUpdateWithArrayValue()
    {
        $result = $this->storage->table('products')->where('id', '=', 3)->update([
            'sku' => 'glue new',
            'data->foo' => ['Foo'],
        ]);
            
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'data->foo' => ['Foo'],
            ],
            $result->item()->all()
        );
        
        $item = $this->storage->table('products')->find(3);
        //$item['data'] = json_decode($item['data'], true);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '33.05',
                'id' => 3,
                'title' => '',
                'data' => '["Foo"]',
            ],
            $item
        );

        $this->assertSame(1, $result->itemsCount());
    }*/
    
    public function testUpdateWithEmptyValueDoesNotAssignValueFromJsonPath()
    {
        $updatedItems = $this->storage->table('products')->where('id', '=', 2)->update([
            'sku' => 'glue new',
            'data->foo' => 'Foo',
        ]);
            
        /*$this->assertEquals(
            [
                'id' => 2,
                'sku' => 'glue new',
                'data' => '',
                'price' => '1.56',
                'title' => '',
            ],
            $updatedItems->first()
        );*/
        
        $this->assertEquals(
            'update',
            $updatedItems->action()
        );
        
        $item = $this->storage->table('products')->find(2)?->all();
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '1.56',
                'id' => 2,
                'title' => '',
                'data' => '',
            ],
            $item
        );

        $this->assertSame(1, $updatedItems->count());
    }
    
    public function testUpdateSetValueIfPathDoesNotExist()
    {
        $updatedItems = $this->storage->table('products')->where('id', '=', 3)->update([
            'sku' => 'glue new',
            'data->cars' => 'bmw',
            'data->country' => 'CH',
        ]);
        
        $this->assertEquals(
            'update',
            $updatedItems->action()
        );
        
        $item = $this->storage->table('products')->find(3)?->all();
        $item['data'] = json_decode($item['data'], true);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => '33.05',
                'id' => 3,
                'title' => '',
                'data' => json_decode(
                    '{"color": "blue", "colors": ["blue", "red"], "foo": null, "options": {"language": "en"}, "cars": "bmw", "country": "CH"}',
                    true
                ),
            ],
            $item
        );

        $this->assertSame(1, $updatedItems->count());
    }    
}