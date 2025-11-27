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

namespace Tobento\Service\Storage\Test\PdoSqliteStorage;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\PdoSqliteStorage;
use Tobento\Service\Database\PdoDatabase;
use Tobento\Service\Database\Processor\PdoSqliteProcessor;
use Tobento\Service\Database\Schema\Table;
use PDO;

/**
 * StorageJsonInsertUpdateTest
 */
class StorageJsonInsertUpdateTest extends \Tobento\Service\Storage\Test\StorageJsonInsertUpdate
{
    protected null|PdoDatabase $database = null;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $pdo = new PDO(
            dsn: 'sqlite::memory:',
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
        
        $this->database = new PdoDatabase(pdo: $pdo, name: 'name');        
 
        $processor = new PdoSqliteProcessor();
        $processor->process($this->tableProducts, $this->database);
        $processor->process($this->tableProductsLg, $this->database);
        
        $this->storage = new PdoSqliteStorage($pdo, $this->tables);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        
        $this->dropTable($this->tableProducts);
        $this->dropTable($this->tableProductsLg);
    }
    
    protected function dropTable(null|Table $table): void
    {
        if (is_null($table) || is_null($this->database)) {
            return;
        }
        
        $table->dropTable();
        
        new PdoSqliteProcessor()->process($table, $this->database);
    }
    
    public function testInsertWithColumnsSpecifiedTwice()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'glue new',
            'data->foo' => 'Foo',
            'data->bar' => 'Bar',
        ], return: ['id']);
            
        $this->assertEquals(
            [
                'id' => 7,
            ],
            $insertedItem->all()
        );
        
        $this->assertEquals(
            'insert',
            $insertedItem->action()
        );
        
        $item = $this->storage->table('products')->find(7)?->all();
        unset($item['title']);
        unset($item['price']);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'id' => 7,
                'data' => 'Bar',
            ],
            $item
        );
    }
    
    public function testInsertWithArrayValue()
    {
        $insertedItem = $this->storage->table('products')->insert([
            'sku' => 'glue new',
            'data->foo' => ['Foo'],
        ], return: ['id']);
        
        $this->assertEquals(
            [
                'id' => 7,
            ],
            $insertedItem->all()
        );
        
        $this->assertEquals(
            'insert',
            $insertedItem->action()
        );
        
        $item = $this->storage->table('products')->find(7)?->all();
        unset($item['title']);
        unset($item['price']);
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'id' => 7,
                'data' => '["Foo"]',
            ],
            $item
        );
    }
    
    public function testUpdateWithEmptyValueDoesNotAssignValueFromJsonPath()
    {
        $updatedItems = $this->storage->table('products')->where('id', '=', 2)->update([
            'sku' => 'glue new',
            'data->foo' => 'Foo',
        ]);
        
        $this->assertEquals(
            'update',
            $updatedItems->action()
        );
        
        $item = $this->storage->table('products')->find(2)?->all();
        
        $this->assertEquals(
            [
                'sku' => 'glue new',
                'price' => 1.56,
                'id' => 2,
                'title' => '',
                'data' => '{"foo":"Foo"}',
            ],
            $item
        );

        $this->assertSame(1, $updatedItems->count());
    }    
}