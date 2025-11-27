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
 * StorageJsonContainsAndLengthTest
 */
class StorageJsonContainsAndLengthTest extends \Tobento\Service\Storage\Test\StorageJsonContainsAndLength
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
    
    public function testWhereJsonContainsStrictComparisonGetMethod()
    {        
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->numbers', [4, 6])->get();
        
        $this->assertEquals(
            [6 => $this->products[6]],
            $items->all()
        );
        
        $items = $this->storage->table('products')->index('id')->whereJsonContains('data->numbers', [4, "6"])->get();
        
        $this->assertEquals(
            [6 => $this->products[6]],
            $items->all()
        );        
    }
    
    public function testWhereJsonLengthWihtoutDelimiterGetMethod()
    {
        // SQLite counts only arrays.
        
        $items = $this->storage->table('products')->index('id')->whereJsonLength('data', '>', 3)->get();
        
        $this->assertEquals(
            [],
            $items->all()
        );       
    }
    
    public function testWhereJsonLengthMultipleGetMethod()
    {
        // SQLite counts only arrays.
        
        $items = $this->storage->table('products')
            ->index('id')
            ->whereJsonLength('data->colors', '>', 1)
            ->whereJsonLength('data->options', '>=', 1)
            ->get();
        
        $this->assertEquals(
            [],
            $items->all()
        );       
    }
    
    public function testWhereJsonLengthOrWhereJsonLengthGetMethod()
    {
        // SQLite counts only arrays.
        
        $items = $this->storage->table('products')
            ->index('id')
            ->whereJsonLength('data->colors', '>', 1)
            ->orWhereJsonLength('data->options', '=', 1)
            ->get();
        
        $this->assertEquals(
            [3 => $this->products[3], 5 => $this->products[5]],
            $items->all()
        );       
    }
}