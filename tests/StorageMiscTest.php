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
 * StorageMiscTest
 */
abstract class StorageMiscTest extends TestCase
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
            1 => ['id' => 1, 'sku' => 'paper', 'price' => '12.00', 'title' => ''],
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
        $tableProducts->items($this->products);
        $this->tableProducts = $tableProducts;
        
        $tableProductsLg = new Table(name: 'products_lg');
        $tableProductsLg->bigInt('product_id');
        $tableProductsLg->int('language_id');
        $tableProductsLg->string('title')->nullable(false)->default('');
        $tableProductsLg->text('description');
        $tableProductsLg->items($this->productsLg);
        $this->tableProductsLg = $tableProductsLg;
    }

    public function tearDown(): void
    {
        //$this->storage = null;
    }
    
    public function testFetchItemsMethod()
    {
        $items = $this->storage->fetchItems('products');
        $items = is_array($items) ? $items : iterator_to_array($items);
            
        $this->assertEquals(
            array_values($this->products),
            array_values($items)
        );
    }
    
    public function testFetchItemsMethodReturnsEmptyIterableIfTableDoesNotExist()
    {
        $items = $this->storage->fetchItems('invalid');
        $items = is_array($items) ? $items : iterator_to_array($items);
        
        $this->assertEquals([], $items);
    }
    
    public function testStoreItemsMethod()
    {
        $storeData = [
            ['id' => 1, 'sku' => 'foo', 'price' => '3.00', 'title' => ''],
            ['id' => 2, 'sku' => 'bar', 'price' => '5.56', 'title' => ''],
        ];
        
        $items = $this->storage->storeItems('products', $storeData);
        $items = is_array($items) ? $items : iterator_to_array($items);
        
        $this->assertEquals(
            array_values($storeData),
            array_values($items)
        );
        
        $fetchedItems = $this->storage->fetchItems('products');
        $fetchedItems = is_array($fetchedItems) ? $items : iterator_to_array($fetchedItems);
        
        $this->assertEquals(
            array_values($fetchedItems),
            array_values($items)
        );        
    }
}