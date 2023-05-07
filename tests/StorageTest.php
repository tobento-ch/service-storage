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
 * StorageTest
 */
abstract class StorageTest extends TestCase
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
    
    public function testNewMethod()
    {        
        $storage = $this->storage->new();
        
        $this->assertFalse($storage === $this->storage);
        $this->assertFalse($storage->tables() === $this->storage->tables());
    }    

    public function testGetMethodWithoutTableSetThrowsGrammarException()
    {
        $this->expectException(GrammarException::class);
        
        $items = $this->storage->get();
    }
    
    public function testGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->get();
        
        $this->assertEquals($this->products, $items->all());
    }
    
    public function testWhereGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', '=', 'glue')->get();
        
        $this->assertEquals([3 => $this->products[3]], $items->all());      
    }    
    
    public function testWhereNotEqualGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', '!=', 'glue')->get();
        
        $products = $this->products;
        unset($products[3]);
        
        $this->assertEquals($products, $items->all());
        
        $items = $this->storage->table('products')->index('id')->where('price', '<>', 12)->get();
        
        $this->assertEquals(
            [
                2 => $this->products[2],
                3 => $this->products[3],
            ],
            $items->all()
        );
    }  
    
    public function testWhereGreaterGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 12)->get();
        
        $this->assertEquals([3 => $this->products[3]], $items->all());
    }
    
    public function testWhereWithStringValueGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', '12')->get();
        
        $this->assertEquals([3 => $this->products[3]], $items->all());
    }    
    
    public function testWhereGreaterOrEqualGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>=', 33.05)->get();
        
        $this->assertEquals([3 => $this->products[3]], $items->all());        
    }

    public function testWhereSmallerGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '<', 12)->get();
        
        $this->assertEquals(
            [
                2 => $this->products[2],
            ],
            $items->all()
        );       
    }
    
    public function testWhereSmallerOrEqualGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '<=', 12)->get();
        
        $this->assertEquals(
            [
                1 => $this->products[1],
                2 => $this->products[2],
            ],
            $items->all()
        );        
    }
    
    public function testWhereSpaceshipGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '<=>', 12)->get();
        
        $this->assertEquals([1 => $this->products[1]], $items->all());
        
        $items = $this->storage->table('products')->index('id')->where('price', '<=>', 13)->get();
        
        $this->assertEquals([], $items->all());
    }

    public function testWhereLikeGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'like', 'pen')->get();
        
        $this->assertEquals(
            [2 => $this->products[2]],
            $items->all()
        );       
    }
    
    public function testWhereLikeAnyPositionGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'like', '%pen%')->get();
        
        $this->assertEquals(
            [2 => $this->products[2], 4 => $this->products[4]],
            $items->all()
        );       
    }
    
    public function testWhereLikeStartsWithGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'like', 'pe%')->get();
        
        $this->assertEquals(
            [2 => $this->products[2], 4 => $this->products[4]],
            $items->all()
        );
    }
    
    public function testWhereLikeEndsWithGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'like', '%en')->get();
        
        $this->assertEquals(
            [2 => $this->products[2]],
            $items->all()
        );       
    }
    
    public function testWhereNotLikeGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'not like', 'pen')->get();
        
        $this->assertEquals(
            [
                1 => $this->products[1],
                3 => $this->products[3],
                5 => $this->products[5],
                4 => $this->products[4],
                6 => $this->products[6],
            ],
            $items->all()
        );       
    }
    
    public function testWhereNotLikeAnyPositionGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'not like', '%pe%')->get();
        
        $this->assertEquals(
            [
                3 => $this->products[3],
                5 => $this->products[5],
                6 => $this->products[6],
            ],
            $items->all()
        );
    }
    
    public function testWhereNotLikeStartsWithGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'not like', 'p%')->get();
        
        $this->assertEquals(
            [
                3 => $this->products[3],
                5 => $this->products[5],
                6 => $this->products[6],
            ],
            $items->all()
        );     
    }
    
    public function testWhereNotLikeEndsWithGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('sku', 'not like', '%n')->get();
        
        $this->assertEquals(
            [
                1 => $this->products[1],
                3 => $this->products[3],
                4 => $this->products[4],
                5 => $this->products[5],
                6 => $this->products[6],
            ],
            $items->all()
        );      
    } 
    
    /*public function testWhereSubQueryGetMethod()
    {        
        $items = $this->storage->table('products')
                               ->where('sku', '=', function($query) {
                                 $query->select('id')
                                       ->table('products') // table is required, otherwise it gets not assigned
                                       ->where('id', '=', 2);                                  
                               })->get();
        
        $this->assertEquals(
            [
                1 => $this->products[1],
                3 => $this->products[3],
                4 => $this->products[4],
                5 => $this->products[5],
                6 => $this->products[6],
            ],
            $items
        );      
    }*/     
    
    public function testWhereInGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereIn('id', [3, 2])->get();
        
        $this->assertEquals(
            [2 => $this->products[2], 3 => $this->products[3]],
            $items->all()
        );       
    }
    
    public function testWhereNotInGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotIn('id', [3, 2, 1, 5])->get();
        
        $this->assertEquals(
            [4 => $this->products[4], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereNullGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNull('price')->get();
        
        $this->assertEquals(
            [4 => $this->products[4], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }
    
    public function testWhereNotNullGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotNull('price')->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 2 => $this->products[2], 3 => $this->products[3]],
            $items->all()
        );       
    }    
    
    public function testOrWhereGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->orWhere('sku', '=', 'paper')->get();
        
        $this->assertEquals([1 => $this->products[1]], $items->all());
        
        $items = $this->storage->table('products')->index('id')->orWhere('sku', '=', 'foo')->get();
        
        $this->assertSame([], $items->all());        
    }
    
    public function testWhereOrWhereGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '<=>', 12)->orWhere('sku', '=', 'paper')->get();
        
        $this->assertEquals(
            [1 => $this->products[1]],
            $items->all()
        );        
    }
    
    public function testWhereOrWhereLikeGetMethod()
    {
        $items = $this->storage->table('products')->index('id')->where('price', '<=>', 12)->orWhere('sku', 'like', 'brush')->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 6 => $this->products[6]],
            $items->all()
        );        
    }
    
    public function testWhereOrWhereInGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 1.8)->orWhereIn('id', [1,3])->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 3 => $this->products[3]],
            $items->all()
        );        
    }
    
    public function testWhereOrWhereNotInGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 12)->orWhereNotIn('id', [1,2,5,6])->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 4 => $this->products[4]],
            $items->all()
        );        
    }
    
    public function testWhereOrWhereNullGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 12)->orWhereNull('price')->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 4 => $this->products[4], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );        
    }

    public function testWhereOrWhereNotNullGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 12)->orWhereNotNull('price')->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 2 => $this->products[2], 3 => $this->products[3]],
            $items->all()
        );        
    }

    public function testWhereBetweenGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereBetween('price', [3, 15])->get();
        
        $this->assertEquals(
            [1 => $this->products[1]],
            $items->all()
        );        
    }
 
    public function testWhereOrWhereBetweenGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 12)->orWhereBetween('price', [3, 15])->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 3 => $this->products[3]],
            $items->all()
        );        
    }
    
    public function testWhereNotBetweenGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotBetween('price', [3, 15])->get();
        
        $this->assertEquals(
            [
                2 => $this->products[2],
                3 => $this->products[3],
            ],
            $items->all()
        );        
    }    
 
    public function testWhereOrWhereNotBetweenGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->where('price', '>', 12)->orWhereNotBetween('price', [3, 15])->get();
        
        $this->assertEquals(
            [
                2 => $this->products[2],
                3 => $this->products[3],
            ],
            $items->all()
        );        
    }
    
    public function testWhereColumnGetMethod()
    {        
        $items = $this->storage->table('products_lg')->index('product_id')->whereColumn('product_id', '=', 'language_id')->get();
        
        $this->assertEquals(
            [
                1 => $this->productsLg[0],
                2 => $this->productsLg[3],
            ],
            $items->all()
        );        
    }
    
    public function testWhereColumnWithValueSubqueryGetMethod()
    {
        $items = $this->storage->table('products_lg')
                               ->whereColumn('product_id', '=', function($query) {
                                    $query->select('id')
                                          ->table('products') // table is required, otherwise it gets not assigned
                                          ->where('id', '=', 2);
                               })
                               ->get();
        
        $this->assertEquals(
            [
                0 => $this->productsLg[2],
                1 => $this->productsLg[3],
            ],
            $items->all()
        );        
    }
    
    public function testWhereOrWhereColumnGetMethod()
    {        
        $items = $this->storage->table('products_lg')
                               ->index('product_id')
                               ->where('title', '=', 'Papier')
                               ->orWhereColumn('product_id', '=', 'language_id')
                               ->get();
        
        $this->assertEquals(
            [
                1 => $this->productsLg[0],
                2 => $this->productsLg[3],
            ],
            $items->all()
        );        
    }     
    
    public function testOrderAscGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotNull('price')->order('price')->get();
        
        $this->assertEquals(
            [2 => $this->products[2], 1 => $this->products[1], 3 => $this->products[3]],
            $items->all()
        );
        
        $items = $this->storage->table('products')->index('id')->whereNotNull('price')->order('price', 'asc')->get();
        
        $this->assertEquals(
            [2 => $this->products[2], 1 => $this->products[1], 3 => $this->products[3]],
            $items->all()
        );
    }
    
    public function testOrderDescGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereNotNull('price')->order('price', 'desc')->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 1 => $this->products[1], 2 => $this->products[2]],
            $items->all()
        );        
    }
    
    public function testLimitNumberGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->limit(2)->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 2 => $this->products[2]],
            $items->all()
        );        
    }
    
    public function testLimitNumberWithOffsetGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->limit(2, 2)->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 4 => $this->products[4]],
            $items->all()
        );        
    }

    public function testLimitWithOffsetExceededGetMethod()
    {        
        $items = $this->storage->table('products')->limit(2, 20)->get();
        
        $this->assertEquals(
            [],
            $items->all()
        );        
    }
    
    public function testGetMethodActionMethod()
    {        
        $items = $this->storage->table('products')->index('id')->get();
        
        $this->assertSame('get', $items->action());
    }
    
    public function testFindMethod()
    {        
        $item = $this->storage->table('products')->find(5);
        
        $this->assertEquals(
            $this->products[5],
            $item?->all()
        );
        
        $item = $this->storage->table('products')->find(15);
        
        $this->assertEquals(
            null,
            $item
        );
    }
    
    public function testFindMethodActionMethod()
    {        
        $item = $this->storage->table('products')->find(5);
        
        $this->assertSame('find', $item->action());
    }    

    public function testFirstMethod()
    {        
        $item = $this->storage->table('products')->first();
        
        $this->assertEquals(
            $this->products[1],
            $item?->all()
        );        
    }
    
    public function testFirstMethodActionMethod()
    {        
        $item = $this->storage->table('products')->first();
        
        $this->assertSame('first', $item->action());
    }
    
    public function testValueMethod()
    {        
        $value = $this->storage->table('products')->order('price', 'desc')->value('sku');
        
        $this->assertEquals(
            'glue',
            $value
        );        
    }

    public function testValueMethodWithInvalidColumnReturnsFirstItemValue()
    {        
        $value = $this->storage->table('products')->order('price', 'desc')->value('foo');
        
        $this->assertEquals(
            3,
            $value
        );        
    }
    
    public function testColumnMethod()
    {        
        $column = $this->storage->table('products')->column('price');
        
        $this->assertEquals(
            [0 => 12, 1 => 1.56, 2 => 33.05, 3 => null, 4 => null, 5 => null],
            $column->all()
        );        
    }
    
    public function testColumnMethodActionMethod()
    {        
        $item = $this->storage->table('products')->column('price');
        
        $this->assertSame('column', $item->action());
    }    

    public function testColumnWithKeyMethod()
    {        
        $column = $this->storage->table('products')->column('price', 'sku');
        
        $this->assertEquals(
            [
                'paper' => 12,
                'pen' => 1.56,
                'glue' => 33.05,
                'pencil' => null,
                'scissors' => null,
                'brush' => null,
            ],
            $column->all()
        );        
    }

    public function testCountMethod()
    {        
        $value = $this->storage->table('products')->count();
        
        $this->assertEquals(
            6,
            $value
        );
        
        $value = $this->storage->table('products')->where('sku', 'like', 'pen')->count();
        
        $this->assertEquals(
            1,
            $value
        );
    }    
}