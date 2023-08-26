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
 * Storage or where tests
 */
abstract class StorageWhereOrTest extends TestCase
{
    protected null|StorageInterface $storage = null;
    protected null|TablesInterface $tables = null;
    
    protected array $products = [];
    
    protected null|Table $tableProducts = null;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'title', 'sku', 'price', 'data'], 'id');
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('title')->nullable(false)->default('');
        $tableProducts->string('sku')->nullable(false)->default('');
        $tableProducts->decimal('price', 15, 2);
        $tableProducts->json('data')->nullable(true)->default(null);
        $tableProducts->items($this->products);
        $this->tableProducts = $tableProducts;
    }

    public function tearDown(): void
    {
        //
    }
    
    public function testWhere()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Foo', 'sku' => 'foo',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Bar', 'sku' => 'bar',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Baz', 'sku' => 'baz',
        ]);
        
        $result = $this->storage
            ->table('products')
            ->where('sku', '=', 'foo')
            ->orWhere('sku', '=', 'baz')
            ->column('sku');
        
        $this->assertSame(['foo', 'baz'], $result->all());
    }
    
    public function testWhereOrWhereWithNotFound()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Foo', 'sku' => 'foo',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Bar', 'sku' => 'bar',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Baz', 'sku' => 'baz',
        ]);
        
        $result = $this->storage
            ->table('products')
            ->where('sku', '=', 'foo')
            ->orWhere('sku', '=', 'zoo')
            ->column('sku');
        
        $this->assertSame(['foo'], $result->all());
    }    

    public function testReturnsNullIfNoMatch()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Foo', 'sku' => 'foo',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Bar', 'sku' => 'bar',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Baz', 'sku' => 'baz',
        ]);
        
        $result = $this->storage
            ->table('products')
            ->where('sku', '=', 'lorem')
            ->orWhere('sku', '=', 'lorem')
            ->count();
        
        $this->assertSame(0, $result);
    }
    
    public function testOrWhereOrWhereReturnsNullIfNoMatch()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Foo', 'sku' => 'foo',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Bar', 'sku' => 'bar',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Baz', 'sku' => 'baz',
        ]);
        
        $result = $this->storage
            ->table('products')
            ->orWhere('sku', '=', 'lorem')
            ->orWhere('sku', '=', 'lorem')
            ->count();
        
        $this->assertSame(0, $result);
    }
    
    public function testPrecedence()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Foo', 'sku' => 'foo',
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Bar', 'sku' => 'bar',
        ]);
        
        $result = $this->storage
            ->table('products')
            ->where('title', '=', 'Foo')
            ->orWhere('sku', '=', 'foo')
            ->orWhere('sku', '=', 'bar')
            ->count();
        
        $this->assertSame(2, $result);
        
        $result = $this->storage
            ->table('products')
            ->where('title', '=', 'Foo')
            ->where(function ($q) {
                $q->where('sku', '=', 'foo');
                $q->orWhere('sku', '=', 'bar');
            })
            ->count();
        
        $this->assertSame(1, $result);
    }    
}