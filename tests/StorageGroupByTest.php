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
 * StorageGroupByTest
 */
abstract class StorageGroupByTest extends TestCase
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
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description', 'options']);
        
        $this->products = [
            1 => ['id' => 1, 'sku' => 'paper', 'price' => '12.00', 'title' => ''],
            2 => ['id' => 2, 'sku' => 'pen', 'price' => '1.56', 'title' => ''],
            3 => ['id' => 3, 'sku' => 'glue', 'price' => '33.05', 'title' => ''],
            4 => ['id' => 4, 'sku' => 'pencil', 'price' => null, 'title' => ''],
            5 => ['id' => 5, 'sku' => 'scissors', 'price' => null, 'title' => ''],
            6 => ['id' => 6, 'sku' => 'brush', 'price' => null, 'title' => ''],
        ];
        
        $this->productsLg = [
            ['product_id' => 1, 'language_id' => 1, 'title' => 'Papier', 'description' => '180mg Papier', 'options' => ''],
            ['product_id' => 1, 'language_id' => 2, 'title' => 'Paper', 'description' => '180mg paper', 'options' => ''],
            ['product_id' => 2, 'language_id' => 1, 'title' => 'Stift', 'description' => 'Wasserfester Stift', 'options' => ''],
            ['product_id' => 2, 'language_id' => 2, 'title' => 'Pen', 'description' => '', 'options' => ''],
            ['product_id' => 3, 'language_id' => 1, 'title' => 'Leim', 'description' => '', 'options' => ''],
            ['product_id' => 3, 'language_id' => 2, 'title' => 'Glue', 'description' => '', 'options' => ''],
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
        $tableProductsLg->json('options');
        $tableProductsLg->items($this->productsLg);
        $this->tableProductsLg = $tableProductsLg;
    }

    public function tearDown(): void
    {
        //$this->storage = null;
    }    

    public function testGetMethodWithoutTableSetThrowsGrammarException()
    {
        $this->expectException(GrammarException::class);
        
        $items = $this->storage->get();
    }

    public function testGroupBy()
    {        
        $items = $this->storage->table('products_lg')
            ->groupBy('product_id')
            ->get();
        
        $this->assertEquals(
            [
                0 => $this->productsLg[0],
                1 => $this->productsLg[2],
                2 => $this->productsLg[4],
            ],
            array_values($items->all())
        );
    }
    
    public function testGroupByAndHaving()
    {        
        $items = $this->storage->table('products_lg')
            ->groupBy('product_id')
            ->having('product_id', '>', 2)
            ->get();
        
        $this->assertEquals(
            [
                0 => $this->productsLg[4],
            ],
            array_values($items->all())
        );
    }
    
    public function testGroupByAndHavingBetween()
    {        
        $items = $this->storage->table('products_lg')
            ->groupBy('product_id')
            ->havingBetween('product_id', [2,4])
            ->get();
        
        $this->assertEquals(
            [
                0 => $this->productsLg[2],
                1 => $this->productsLg[4],
            ],
            array_values($items->all())
        );
    }    
}