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

class StorageWhereOrTest extends \Tobento\Service\Storage\Test\StorageWhereOr
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
        
        $this->storage = new PdoSqliteStorage($pdo, $this->tables);
    }

    public function tearDown(): void
    {
        $this->dropTable($this->tableProducts);
    }
    
    protected function dropTable(null|Table $table): void
    {
        if (is_null($table) || is_null($this->database)) {
            return;
        }
        
        $table->dropTable();
        
        new PdoSqliteProcessor()->process($table, $this->database);
    }
}