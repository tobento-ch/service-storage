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
 * StorageJoinsTest
 */
abstract class StorageJoinsTest extends TestCase
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

    public function testJoinLimitGetMethod()
    {        
        $items = $this->storage->table('products p')
                               ->join('products_lg pl', 'id', '=', 'product_id')
                               ->limit(1)
                               ->get();
        
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'sku' => 'paper',
                    'price' => 12,
                    'product_id' => 1,
                    'language_id' => 1,
                    'title' => 'Papier',
                    'description' => '180mg Papier',
                ]
            ],
            $items->all()
        );        
    }
    
    public function testJoinCountMethod()
    {        
        $items = $this->storage->table('products p')
                               ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                               ->count();
        
        $this->assertEquals(6, $items);
    }
    
    public function testJoinCountMethodWithOnOr()
    {        
        $items = $this->storage->table('products p')
                               ->join('products_lg pl', function($join) {
                                    $join->on('p.id', '=', 'pl.product_id')
                                         ->orOn('p.id', '=', 'pl.language_id');         
                               })
                               ->count();
        
        $this->assertEquals(10, $items);
    }  
    
    public function testLeftJoinLimitGetMethod()
    {        
        $items = $this->storage->table('products p')
                               ->leftJoin('products_lg pl', 'id', '=', 'product_id')
                               ->limit(1)
                               ->get();
        
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'sku' => 'paper',
                    'price' => 12,
                    'product_id' => 1,
                    'language_id' => 1,
                    'title' => 'Papier',
                    'description' => '180mg Papier',
                ]
            ],
            $items->all()
        );        
    }

    public function testLeftJoinCountMethod()
    {        
        $items = $this->storage->table('products p')
                               ->leftJoin('products_lg pl', 'p.id', '=', 'pl.product_id')
                               ->count();
        
        $this->assertEquals(9, $items);
    }
    
    public function testLeftJoinCountMethodWithOnOr()
    {        
        $items = $this->storage->table('products p')
                               ->leftJoin('products_lg pl', function($join) {
                                    $join->on('p.id', '=', 'pl.product_id')
                                         ->orOn('p.id', '=', 'pl.language_id');         
                               })
                               ->count();
        
        $this->assertEquals(13, $items);
    }    
    
    public function testRightJoinLimitGetMethod()
    {        
        $items = $this->storage->table('products p')
                               ->rightJoin('products_lg pl', 'id', '=', 'product_id')
                               ->limit(1)
                               ->get();
        
        $this->assertEquals(
            [
                0 => [
                    'id' => 1,
                    'sku' => 'paper',
                    'price' => 12,
                    'product_id' => 1,
                    'language_id' => 1,
                    'title' => 'Papier',
                    'description' => '180mg Papier',
                ]
            ],
            $items->all()
        );
    }
    
    public function testRightJoinCountMethod()
    {        
        $items = $this->storage->table('products p')
                               ->rightJoin('products_lg pl', 'id', '=', 'product_id')
                               ->count();
        
        $this->assertEquals(6, $items);
    }

    public function testRightJoinCountMethodWithOnOr()
    {        
        $items = $this->storage->table('products p')
                               ->rightJoin('products_lg pl', function($join) {
                                    $join->on('p.id', '=', 'pl.product_id')
                                         ->orOn('p.id', '=', 'pl.language_id');         
                               })
                               ->count();
        
        $this->assertEquals(10, $items);
    }    
}