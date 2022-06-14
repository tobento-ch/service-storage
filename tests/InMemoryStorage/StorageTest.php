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
 * StorageTest
 */
class StorageTest extends \Tobento\Service\Storage\Test\StorageTest
{
    public function setUp(): void
    {
        parent::setUp();
        
        $this->storage = new InMemoryStorage([
            'products' => $this->products,
            'products_lg' => $this->productsLg,    
        ], $this->tables);
    }
    
    public function testWhereColumnWithValueSubqueryGetMethod()
    {
        $items = $this->storage->table('products_lg')
                               ->whereColumn('product_id', '=', function($query) {
                                    $query->select('id')
                                          ->table('products') // table is required, otherwise it gets not assigned
                                          ->where('id', '=', 2);
                               })
                               ->get();
        
        $this->assertEquals(
            [
                2 => $this->productsLg[2],
                3 => $this->productsLg[3],
            ],
            $items->all()
        );        
    }   
}