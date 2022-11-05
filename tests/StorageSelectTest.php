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
 * StorageSelectTest
 */
abstract class StorageSelectTest extends TestCase
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

    public function testSelectGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->select('sku')->limit(1)->get();
        
        $this->assertEquals(
            [1 => ['sku' => 'paper']],
            $items->all()
        );        
    }
    
    public function testSelectWithSameColumnGetMethod()
    {        
        $items = $this->storage->table('products')
            ->index('id')
            ->select('sku as asku', 'sku as bsku')
            ->limit(1)
            ->get();
        
        $this->assertEquals(
            [1 => ['asku' => 'paper', 'bsku' => 'paper']],
            $items->all()
        );        
    }    
    
    public function testSelectGetMethodWithTableAlias()
    {        
        $items = $this->storage->table('products p')->index('id')->select('sku')->limit(1)->get();
        
        $this->assertEquals(
            [1 => ['sku' => 'paper']],
            $items->all()
        );
        
        $items = $this->storage->table('products p')->index('id')->select('p.sku')->limit(1)->get();
        
        $this->assertEquals(
            [1 => ['sku' => 'paper']],
            $items->all()
        );         
    }
    
    public function testSelectGetMethodWithWrongTableAlias()
    {
        // returns all columns as a.sku does not exists, so fallbacks to empty columns,
        // which returns all columns.
        $items = $this->storage->table('products p')->index('id')->select('a.sku')->limit(1)->get();
        
        $this->assertEquals(
            [1 => $this->products[1]],
            $items->all()
        );
        
        $items = $this->storage->table('products p')->index('id')->select('a.sku as foo')->limit(1)->get();
        
        $this->assertEquals(
            [1 => $this->products[1]],
            $items->all()
        );         
    }

    public function testSelectGetMethodWithTableAliasAndJoinedTable()
    {        
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku', 'description')
                      ->limit(1)
                      ->get();
        
        $this->assertEquals(
            [0 => ['sku' => 'paper', 'description' => '180mg Papier']],
            $items->all()
        );
        
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('p.sku', 'pl.description')
                      ->limit(1)
                      ->get();
        
        $this->assertEquals(
            [0 => ['sku' => 'paper', 'description' => '180mg Papier']],
            $items->all()
        );
        
        // verifier gets right table alias
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('p.sku', 'a.description')
                      ->limit(1)
                      ->get();
        
        $this->assertEquals(
            [0 => ['sku' => 'paper']],
            $items->all()
        );         
    }
 
    public function testSelectGetMethodWithTableAliasAndJoinedTableWithSameColumnNames()
    {
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku', 'title')
                      ->limit(1)
                      ->get();
        
        // returns title from default table
        $this->assertEquals(
            [0 => ['sku' => 'paper', 'title' => 'Papier']],
            $items->all()
        );
        
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku', 'p.title')
                      ->limit(1)
                      ->get();
        
        // returns title from join table as column is alias
        $this->assertEquals(
            [0 => ['sku' => 'paper', 'title' => 'Blatt']],
            $items->all()
        );
        
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku as foo', 'pl.title')
                      ->limit(1)
                      ->get();
        
        $this->assertEquals(
            [0 => ['foo' => 'paper', 'title' => 'Papier']],
            $items->all()
        );        
    }
    
    public function testSelectFirstMethodWithTableAliasAndJoinedTableWithSameColumnNames()
    {
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku', 'title')
                      ->first();
        
        // returns title from default table
        $this->assertEquals(
            ['title' => 'Papier', 'sku' => 'paper'],
            $items->all()
        );
        
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku', 'pl.title')
                      ->first();
        
        $this->assertEquals(
            ['title' => 'Papier', 'sku' => 'paper'],
            $items->all()
        );
        
        $items = $this->storage
                      ->table('products p')
                      ->join('products_lg pl', 'p.id', '=', 'pl.product_id')
                      ->select('sku', 'pl.title as foo')
                      ->first();
        
        $this->assertEquals(
            ['foo' => 'Papier', 'sku' => 'paper'],
            $items->all()
        );        
    }    
        
    public function testSelectFirstMethod()
    {        
        $items = $this->storage->table('products')->select('sku')->first();
        
        $this->assertEquals(
            ['sku' => 'paper'],
            $items->all()
        );        
    }

    public function testSelectFindMethod()
    {        
        $items = $this->storage->table('products')->select('sku')->find(5);
        
        $this->assertEquals(
            ['sku' => 'scissors'],
            $items->all()
        );        
    }   
}