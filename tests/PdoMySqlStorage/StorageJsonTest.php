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
 * StorageJsonTest
 */
class StorageJsonTest extends \Tobento\Service\Storage\Test\StorageJsonTest
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
    
    public function testWhereNullGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNull('data->color')
            ->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 2 => $this->products[2]],
            $items->all()
        );       
    }
    
    public function testWhereNullMultipleGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNull('data->color')
            ->whereNull('data->material')
            ->get();
        
        $this->assertEquals(
            [1 => $this->products[1], 2 => $this->products[2]],
            $items->all()
        );       
    }
    
    public function testWhereNotNullGetMethod()
    {
        $items = $this->storage->table('products')
            ->index('id')
            ->whereNotNull('data->color')
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 4 => $this->products[4], 5 => $this->products[5], 6 => $this->products[6]],
            $items->all()
        );       
    }    
}