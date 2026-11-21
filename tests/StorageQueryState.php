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
use Tobento\Service\Storage\Grammar\GrammarException;
use RuntimeException;

abstract class StorageQueryState extends TestCase
{
    protected null|StorageInterface $storage = null;
    protected null|TablesInterface $tables = null;
    
    protected array $products = [];
    
    protected null|Table $tableProducts = null;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id');
        
        $this->products = [
            1 => ['id' => 1, 'sku' => 'paper', 'price' => '12.00', 'title' => ''],
            2 => ['id' => 2, 'sku' => 'pen', 'price' => '1.56', 'title' => ''],
            3 => ['id' => 3, 'sku' => 'glue', 'price' => '33.05', 'title' => ''],
        ];
        
        $tableProducts = new Table(name: 'products');
        $tableProducts->bigPrimary('id');
        $tableProducts->string('sku', 100)->nullable(false)->default('');
        $tableProducts->decimal('price', 15, 2);
        $tableProducts->string('title')->nullable(false)->default('');
        $tableProducts->items($this->products);
        $this->tableProducts = $tableProducts;
    }

    public function tearDown(): void
    {
        //
    }
    
    protected function runFailingQuery(): void
    {
        try {
            $this->storage->table('unknown')->where('sku', '=', 'pen')->get();
        } catch (GrammarException $e) {
            return;
        }
        
        $this->fail('The query should have failed.');
    }
    
    public function testGetAfterFailedQueryIsNotAffectedByIt()
    {
        $this->runFailingQuery();
        
        $this->assertSame(3, $this->storage->table('products')->get()->count());
    }
    
    public function testFirstAfterFailedQueryIsNotAffectedByIt()
    {
        $this->runFailingQuery();
        
        $item = $this->storage->table('products')->where('sku', '=', 'glue')->first();
        
        $this->assertSame('glue', $item?->get('sku'));
    }
    
    public function testColumnAfterFailedQueryIsNotAffectedByIt()
    {
        $this->runFailingQuery();
        
        $this->assertSame(
            ['paper', 'pen', 'glue'],
            $this->storage->table('products')->order('id', 'ASC')->column('sku')->all()
        );
    }
    
    public function testCountAfterFailedQueryIsNotAffectedByIt()
    {
        $this->runFailingQuery();
        
        $this->assertSame(3, $this->storage->table('products')->count());
    }
    
    public function testQueryAfterGetQueryWithFailingCallbackIsNotAffectedByIt()
    {
        try {
            $this->storage->getQuery(function (StorageInterface $storage): void {
                $storage->table('products')->where('sku', '=', 'pen')->get();
                throw new RuntimeException('Callback failed.');
            });
        } catch (RuntimeException $e) {
            // ignore
        }
        
        $this->assertSame(3, $this->storage->table('products')->get()->count());
    }
    
    public function testQueryAfterGetQueryIsNotAffectedByIt()
    {
        $this->storage->table('products')->where('sku', '=', 'pen')->getQuery();
        
        $this->assertSame(3, $this->storage->table('products')->get()->count());
    }
}