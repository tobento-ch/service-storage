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

namespace Tobento\Service\Storage\Test\JsonFileStorage;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\StorageException;
use Tobento\Service\Filesystem\Dir;

/**
 * StorageSelectTest
 */
class StorageSelectTest extends \Tobento\Service\Storage\Test\StorageSelectTest
{
    public function setUp(): void
    {
        parent::setUp();
        
        $this->storage = new JsonFileStorage(
            dir: __DIR__.'/../tmp/json-file-storage/',
            tables: $this->tables
        );
        
        $this->storage->storeItems($this->tableProducts->getName(), $this->tableProducts->getItems());
        $this->storage->storeItems($this->tableProductsLg->getName(), $this->tableProductsLg->getItems());
    }
    
    public function tearDown(): void
    {
        parent::tearDown();
        $dir = new Dir();
        $dir->delete($this->storage->dir());
    }
    
    public function testSelectRawGetMethodThrowsStorageException()
    {
        $this->expectException(StorageException::class);
        
        $items = $this->storage->table('products')->selectRaw('')->get();       
    }
    
    public function testSelectAddRawGetMethodThrowsStorageException()
    {
        $this->expectException(StorageException::class);
        
        $items = $this->storage->table('products')->selectAddRaw('')->get();       
    }    
}