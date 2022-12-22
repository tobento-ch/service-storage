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
 * StorageSpecialCharTest
 */
abstract class StorageSpecialCharTest extends TestCase
{
    protected null|StorageInterface $storage = null;
    protected null|TablesInterface $tables = null;
    
    protected array $products = [];
    
    protected null|Table $tableProducts = null;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'title', 'data'], 'id');
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('title')->nullable(false)->default('');
        $tableProducts->json('data')->nullable(true)->default(null);
        $tableProducts->items($this->products);
        $this->tableProducts = $tableProducts;
    }

    public function tearDown(): void
    {
        //
    }

    public function testInsert()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Color Grün',
            'data' => ['color' => 'grün'],
        ]);

        $this->assertEquals(
            ['id' => 1, 'title' => 'Color Grün', 'data' => '{"color":"grün"}'],
            $this->storage->table('products')->first()->all()
        );
    }
    
    public function testUpdate()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Color Grün',
            'data' => [],
        ]);
        
        $updatedItem = $this->storage->table('products')->update([
            'data' => ['color' => 'grün'],
        ]);        

        $this->assertEquals(
            ['id' => 1, 'title' => 'Color Grün', 'data' => '{"color":"grün"}'],
            $this->storage->table('products')->first()->all()
        );
    }    
    
    public function testWhere()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Color Grün',
            'data' => ['color' => 'grün'],
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Color Rot',
            'data' => ['color' => 'rot'],
        ]);
        
        $this->assertEquals(
            1,
            $this->storage->table('products')->where('data->color', '=', 'grün')->count()
        );
        
        $this->assertEquals(
            1,
            $this->storage->table('products')->where('data->color', '=', 'rot')->count()
        );
    }
    
    public function testWhereJsonContains()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Color Grün',
            'data' => ['color' => ['grün']],
        ]);
        
        $insertedItem = $this->storage->table('products')->insert([
            'title' => 'Color Rot',
            'data' => ['color' => ['rot']],
        ]);
        
        $this->assertEquals(
            1,
            $this->storage->table('products')->whereJsonContains('data->color', 'grün')->get()->count()
        );
        
        $this->assertEquals(
            1,
            $this->storage->table('products')->whereJsonContains('data->color', 'rot')->count()
        );
    }
}