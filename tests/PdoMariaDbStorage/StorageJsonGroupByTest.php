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

namespace Tobento\Service\Storage\Test\PdoMariaDbStorage;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\PdoMariaDbStorage;
use Tobento\Service\Database\PdoDatabase;
use Tobento\Service\Database\Processor\PdoMySqlProcessor;
use Tobento\Service\Database\Schema\Table;
use PDO;

/**
 * StorageJsonGroupByTest
 */
class StorageJsonGroupByTest extends \Tobento\Service\Storage\Test\StorageJsonGroupByTest
{
    protected null|PdoDatabase $database = null;
    
    public function setUp(): void
    {
        parent::setUp();
        
        if (! getenv('TEST_TOBENTO_STORAGE_PDO_MARIADB')) {
            $this->markTestSkipped('PdoMySqlProcessor tests are disabled');
        }

        $pdo = new PDO(
            dsn: getenv('TEST_TOBENTO_STORAGE_PDO_MARIADB_DSN'),
            username: getenv('TEST_TOBENTO_STORAGE_PDO_MARIADB_USERNAME'),
            password: getenv('TEST_TOBENTO_STORAGE_PDO_MARIADB_PASSWORD'),
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
        
        $this->database = new PdoDatabase(pdo: $pdo, name: 'name');        
 
        $processor = new PdoMySqlProcessor();
        $processor->process($this->tableProducts, $this->database);
        $processor->process($this->tableProductsLg, $this->database);
        
        $this->storage = new PdoMariaDbStorage($pdo, $this->tables);
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
}