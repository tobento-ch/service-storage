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
use Tobento\Service\Storage\Grammar\GrammarException;

/**
 * StorageJsonTest
 */
abstract class StorageJsonTest extends TestCase
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
            1 => ['id' => 1, 'sku' => 'paper', 'price' => '12.00', 'title' => 'Blatt', 'data' => null],
            2 => ['id' => 2, 'sku' => 'pen', 'price' => '1.56', 'title' => '', 'data' => null],
            3 => ['id' => 3, 'sku' => 'glue', 'price' => '33.05', 'title' => '', 'data' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            4 => ['id' => 4, 'sku' => 'pencil', 'price' => null, 'title' => '', 'data' => '{"color":null,"votes":3}'],
            5 => ['id' => 5, 'sku' => 'scissors', 'price' => null, 'title' => '', 'data' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            6 => ['id' => 6, 'sku' => 'brush', 'price' => null, 'title' => '', 'data' => '{"color":"red","colors":["blue"],"foo": null,"options":{"language":"de"}, "numbers": [4, "6"],"votes":10,"sku":"brush"}'],
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
        $tableProducts->items(new Items($this->products));
        $this->tableProducts = $tableProducts;
        
        $tableProductsLg = new Table(name: 'products_lg');
        $tableProductsLg->bigInt('product_id');
        $tableProductsLg->int('language_id');
        $tableProductsLg->string('title')->nullable(false)->default('');
        $tableProductsLg->text('description');
        $tableProductsLg->json('options');
        $tableProductsLg->items(new Items($this->productsLg));
        $this->tableProductsLg = $tableProductsLg;
    }

    public function tearDown(): void
    {
        //
    }

    public function testWhereGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->where('data->color', '=', 'blue')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereOrWhereGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->where('data->color', '=', 'blue')
            ->orWhere('data->color', '=', 'red')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereLikeGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->where('data->color', 'like', '%bl%')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereInGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereIn('data->color', ['red'])
            ->get();
        
        $this->assertEquals(
            [6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereNotInGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNotIn('data->color', ['blue'])
            ->get();
        
        $this->assertEquals(
            [4 => $this->products[4], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereNullGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNull('data->color')
            ->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 2 => $this->products[2], 4 => $this->products[4]],
            $items->all()
        );       
    }
    
    public function testWhereNotNullGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNotNull('data->color')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereBetweenGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereBetween('data->votes', [2, 5])
            ->get();
        
        $this->assertEquals(
            [4 => $this->products[4]],
            $items->all()
        );       
    }
    
    public function testWhereNotBetweenGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNotBetween('data->votes', [2, 5])
            ->get();
        
        $this->assertEquals(
            [6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereColumnGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereColumn('sku', '=', 'data->sku')
            ->get();
        
        $this->assertEquals(
            [6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testValueMethod()
    {        
        $value = $this->storage->table('products')->order('price', 'desc')->value('data->color');
        
        $this->assertEquals(
            'blue',
            $value
        );        
    }
    
    public function testColumnMethod()
    {        
        $column = $this->storage->table('products')->column('data->color')->all();
        $column[3] = null;
        
        $this->assertEquals(
            [0 => null, 1 => null, 2 => 'blue', 3 => null, 4 => 'blue', 5 => 'red'],
            $column
        );        
    }

    public function testColumnWithSameKeyMethod()
    {
        $column = $this->storage->table('products')->column('data->color', 'data->color')->all();
        unset($column['null']);
        
        $this->assertEquals(
            ['' => null, 'blue' => 'blue', 'red' => 'red'],
            $column
        );        
    }
    
    public function testOrderAscGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotNull('price')->order('data->color')->get();
        
        $this->assertEquals(
            [2 => $this->products[2], 1 => $this->products[1], 3 => $this->products[3]],
            $items->all()
        );
    }
    
    public function testOrderDescGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotNull('price')->order('data->color', 'desc')->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 1 => $this->products[1], 2 => $this->products[2]],
            $items->all()
        );        
    }    
}