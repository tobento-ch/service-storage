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

/**
 * StorageJsonContainsAndLengthTest
 */
class StorageJsonContainsAndLengthTest extends \Tobento\Service\Storage\Test\StorageJsonContainsAndLengthTest
{
    public function setUp(): void
    {
        parent::setUp();
        
        $this->storage = new InMemoryStorage([
            'products' => $this->products,
            'products_lg' => $this->productsLg,    
        ], $this->tables);
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
}