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
use Error;

/**
 * StorageTransactionTest
 */
abstract class StorageTransactionTest extends TestCase
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
        
        $this->products = [];
        
        $this->productsLg = [];
        
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
    }

    public function tearDown(): void
    {
        //$this->storage = null;
    }

    public function testCommitInsert()
    {
        $this->storage->begin();

        $inserted = $this->storage
            ->table('products')
            ->insert([
                'sku' => 'Foo',
            ]);

        $this->assertSame(1, $this->storage->table('products')->count());
        
        $this->storage->commit();
        
        $this->assertSame(1, $this->storage->table('products')->count());
    }
    
    public function testCommitInsertClosure()
    {
        $this->storage->transaction(function($storage) {
            $inserted = $storage
                ->table('products')
                ->insert([
                    'sku' => 'Foo',
                ]);

            $this->assertSame(1, $this->storage->table('products')->count());
        });

        $this->assertSame(1, $this->storage->table('products')->count());
    }
    
    public function testRollbackInsert()
    {
        $this->storage->begin();

        $inserted = $this->storage
            ->table('products')
            ->insert([
                'sku' => 'Foo',
            ]);

        $this->assertSame(1, $this->storage->table('products')->count());
        
        $this->storage->rollback();
        
        $this->assertSame(0, $this->storage->table('products')->count());
    }    
    
    public function testRollbackInsertClosure()
    {
        try {
            $this->storage->transaction(function($storage) {
                $inserted = $storage
                    ->table('products')
                    ->insert([
                        'sku' => 'Foo',
                    ]);
                
                $this->assertSame(1, $this->storage->table('products')->count());

                throw new Error('Something went wrong');
            });
        } catch (Error $e) {
            $this->assertSame('Something went wrong', $e->getMessage());
        }

        $this->assertSame(0, $this->storage->table('products')->count());
    }
    
    public function testRollbackInsertClosureNested()
    {
        try {
            $this->storage->transaction(function($storage) {

                $storage->transaction(function($storage) {

                    $inserted = $storage
                        ->table('products')
                        ->insert([
                            'sku' => 'Nested',
                        ]);
                    
                    $this->assertSame(1, $this->storage->table('products')->count());
                });
                
                $inserted = $storage
                    ->table('products')
                    ->insert([
                        'sku' => 'Foo',
                    ]);
                
                $inserted = $storage
                    ->table('products_lg')
                    ->insert([
                        'product_id' => $inserted->item()->get('id'),
                        'language_id' => 1,
                    ]);                
                
                $this->assertSame(2, $this->storage->table('products')->count());
                $this->assertSame(1, $this->storage->table('products_lg')->count());
                
                throw new Error('Something went wrong');
            });
        } catch (Error $e) {
            $this->assertSame('Something went wrong', $e->getMessage());
        }

        $this->assertSame(0, $this->storage->table('products')->count());
        $this->assertSame(0, $this->storage->table('products_lg')->count());
    }
    
    public function testCommitInsertNested()
    {
        $this->storage->begin();

        $inserted = $this->storage
            ->table('products')
            ->insert([
                'sku' => 'Foo',
            ]);

        $this->assertSame(1, $this->storage->table('products')->count());
        
        $this->storage->begin();
        
        $inserted = $this->storage
            ->table('products')
            ->insert([
                'sku' => 'Bar',
            ]);        
        
        $this->assertSame(
            ['Foo', 'Bar'],
            $this->storage->table('products')->column('sku')->all()
        );
        
        $this->storage->commit();
        
        $this->assertSame(
            ['Foo', 'Bar'],
            $this->storage->table('products')->column('sku')->all()
        );
        
        $this->storage->commit();
        
        $this->assertSame(
            ['Foo', 'Bar'],
            $this->storage->table('products')->column('sku')->all()
        );       
    }
    
    public function testRollbackInsertNested()
    {
        $this->storage->begin();

        $inserted = $this->storage
            ->table('products')
            ->insert([
                'sku' => 'Foo',
            ]);

        $this->assertSame(1, $this->storage->table('products')->count());
        
        $this->storage->begin();
        
        $inserted = $this->storage
            ->table('products')
            ->insert([
                'sku' => 'Bar',
            ]);
        
        $this->assertSame(
            ['Foo', 'Bar'],
            $this->storage->table('products')->column('sku')->all()
        );
        
        $this->storage->rollback();
        
        $this->assertSame(
            ['Foo'],
            $this->storage->table('products')->column('sku')->all()
        );
        
        $this->storage->commit();
        
        $this->assertSame(
            ['Foo'],
            $this->storage->table('products')->column('sku')->all()
        );   
    }
}