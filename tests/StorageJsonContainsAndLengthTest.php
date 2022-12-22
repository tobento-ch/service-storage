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
 * StorageJsonContainsAndLengthTest
 */
abstract class StorageJsonContainsAndLengthTest extends TestCase
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
            4 => ['id' => 4, 'sku' => 'pencil', 'price' => null, 'title' => '', 'data' => null],
            5 => ['id' => 5, 'sku' => 'scissors', 'price' => null, 'title' => '', 'data' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            6 => ['id' => 6, 'sku' => 'brush', 'price' => null, 'title' => '', 'data' => '{"color":"red","colors":["blue"],"foo": null,"options":{"language":"de"}, "numbers": [4, "6"]}'],
        ];
                
        $this->productsLg = [
            ['product_id' => 1, 'language_id' => 1, 'title' => 'Papier', 'description' => '180mg Papier', 'options' => null],
            ['product_id' => 1, 'language_id' => 2, 'title' => 'Paper', 'description' => '180mg paper', 'options' => null],
            ['product_id' => 2, 'language_id' => 1, 'title' => 'Stift', 'description' => 'Wasserfester Stift', 'options' => null],
            ['product_id' => 2, 'language_id' => 2, 'title' => 'Pen', 'description' => '', 'options' => '{"color":"blue","colors":["blue","red"],"foo": null,"options":{"language":"en"}}'],
            ['product_id' => 3, 'language_id' => 1, 'title' => 'Leim', 'description' => '', 'options' => null],
            ['product_id' => 3, 'language_id' => 2, 'title' => 'Glue', 'description' => '', 'options' => null],
        ];        
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('sku', 100)->nullable(false)->default('');
        $tableProducts->decimal('price', 15, 2);
        $tableProducts->string('title')->nullable(false)->default('');
        $tableProducts->json('data')->nullable(true)->default(null);
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

    public function testWhereJsonContainsWithStringValueGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->color', 'blue')->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsWithNullValueGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->foo', null)->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsWithArrayValueGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->colors', ['blue', 'red'])->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsMultipleGetMethod()
    {
        $items = $this->storage->table('products')->index('id')
            ->whereJsonContains('data->foo', null)
            ->whereJsonContains('data->colors', ['blue', 'red'])
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsWithoutDelimiterGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data', 'blue')->get();
        
        $this->assertEquals(
            [],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsStrictComparisonGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->numbers', [4, 6])->get();
        
        $this->assertEquals(
            [],
            $items->all()
        );
        
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->numbers', [4, "6"])->get();
        
        $this->assertEquals(
            [6 => $this->products[6]],
            $items->all()
        );        
    }
    
    public function testWhereJsonContainsOrWhereJsonContainsGetMethod()
    {
        $items = $this->storage->table('products')->index('id')
            ->whereJsonContains('data->foo', null)
            ->orWhereJsonContains('data->options->language', 'de')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsKeyGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->whereJsonContainsKey('data->color')->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsKeyWithoutDelimiterGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->whereJsonContainsKey('data')->get();
        
        $this->assertEquals(
            $this->products,
            $items->all()
        );       
    }    
    
    public function testWhereJsonContainsKeyMultipleGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereJsonContainsKey('data->color')
            ->whereJsonContainsKey('data->foo')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereJsonContainsKeyOrWhereJsonContainsKeyGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereJsonContainsKey('data->color')
            ->orWhereJsonContainsKey('data->bar')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereJsonLengthGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->whereJsonLength('data->colors', '>', 1)->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereJsonLengthWihtoutDelimiterGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->whereJsonLength('data', '>', 3)->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }    
    
    public function testWhereJsonLengthMultipleGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereJsonLength('data->colors', '>', 1)
            ->whereJsonLength('data->options', '>=', 1)
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
    
    public function testWhereJsonLengthOrWhereJsonLengthGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereJsonLength('data->colors', '>', 1)
            ->orWhereJsonLength('data->options', '=', 1)
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }    
}