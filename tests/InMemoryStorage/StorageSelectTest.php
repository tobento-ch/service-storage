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

namespace Tobento\Service\Storage\Test\InMemoryStorage;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Storage\StorageException;

/**
 * StorageSelectTest
 */
class StorageSelectTest extends \Tobento\Service\Storage\Test\StorageSelectTest
{
    public function setUp(): void
    {
        parent::setUp();
        
        $this->storage = new InMemoryStorage([
            'products' => $this->products,
            'products_lg' => $this->productsLg,    
        ], $this->tables);
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