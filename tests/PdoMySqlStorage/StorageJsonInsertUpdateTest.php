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

namespace Tobento\Service\Storage\Test\PdoMySqlStorage;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Database\PdoDatabase;
use Tobento\Service\Database\Processor\PdoMySqlProcessor;
use Tobento\Service\Database\Schema\Table;
use PDO;

/**
 * StorageJsonInsertUpdateTest
 */
class StorageJsonInsertUpdateTest extends \Tobento\Service\Storage\Test\StorageJsonInsertUpdateTest
{
    protected null|PdoDatabase $database = null;
    
    public function setUp(): void
    {
        parent::setUp();
        
        if (! getenv('TEST_TOBENTO_STORAGE_PDO_MYSQL')) {
            $this->markTestSkipped('PdoMySqlProcessor tests are disabled');
        }

        $pdo = new PDO(
            dsn: getenv('TEST_TOBENTO_STORAGE_PDO_MYSQL_DSN'),
            username: getenv('TEST_TOBENTO_STORAGE_PDO_MYSQL_USERNAME'),
            password: getenv('TEST_TOBENTO_STORAGE_PDO_MYSQL_PASSWORD'),
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
        
        $this->database = new PdoDatabase(pdo: $pdo, name: 'name');        
 
        $processor = new PdoMySqlProcessor();
        $processor->process($this->tableProducts, $this->database);
        $processor->process($this->tableProductsLg, $this->database);
        
        $this->storage = new PdoMySqlStorage($pdo, $this->tables);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        
        $this->dropTable($this->tableProducts);
        $this->dropTable($this->tableProductsLg);
    }
    
    protected function dropTable(Table $table): void
    {
        $table->dropTable();
        
        $processor = new PdoMySqlProcessor();
        
        $processor->process($table, $this->database);
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
}