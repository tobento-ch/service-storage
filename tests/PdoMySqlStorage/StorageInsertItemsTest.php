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
 * StorageInsertItemsTest
 */
class StorageInsertItemsTest extends \Tobento\Service\Storage\Test\StorageInsertItemsTest
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
        
        $this->storage = new PdoMySqlStorage($pdo, $this->tables);
    }

    public function tearDown(): void
    {
        $this->dropTable($this->tableProducts);
    }
    
    protected function dropTable(Table $table): void
    {
        $table->dropTable();
        
        $processor = new PdoMySqlProcessor();
        
        $processor->process($table, $this->database);
    }
    
    public function testReturningAllColumnsIfNotSpecified()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen', 'price' => 7.5, 'title' => 'Pen'],
        ]);

        $this->assertEquals(
            [],
            $insertedItems->all()
        );
    }
    
    public function testReturningSpecificColumns()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ], return: ['id']);

        $this->assertEquals(
            [],
            $insertedItems->all()
        );
    }
    
    public function testReturningSpecificColumnsIgnoresInvalid()
    {
        $insertedItems = $this->storage->table('products')->insertItems([
            ['sku' => 'pen'],
        ], return: ['sku', 'unknown']);

        $this->assertEquals(
            [],
            $insertedItems->all()
        );
    }    
}