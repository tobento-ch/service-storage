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
 * StorageMiscTest
 */
class StorageMiscTest extends \Tobento\Service\Storage\Test\StorageMiscTest
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
    
    public function testStoreItemsMethod()
    {
        $storeData = [
            ['id' => 1, 'sku' => 'foo', 'price' => '3.00', 'title' => ''],
            ['id' => 2, 'sku' => 'bar', 'price' => '5.56', 'title' => ''],
        ];
        
        $items = $this->storage->storeItems('products', $storeData);
        $items = is_array($items) ? $items : iterator_to_array($items);
        
        $this->assertEquals(
            [],
            $items
        );
        
        $fetchedItems = $this->storage->fetchItems('products');
        $fetchedItems = is_array($fetchedItems) ? $items : iterator_to_array($fetchedItems);
        
        $this->assertEquals(
            array_values($fetchedItems),
            [
                ['id' => 1, 'sku' => 'foo', 'price' => '3.00', 'title' => ''],
                ['id' => 2, 'sku' => 'bar', 'price' => '5.56', 'title' => ''],
            ]
        );        
    }
    
    public function testPdoMethod()
    {
        $this->assertInstanceof(PDO::class, $this->storage->pdo());
    }
}