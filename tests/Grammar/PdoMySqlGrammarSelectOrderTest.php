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

namespace Tobento\Service\Storage\Test\Grammar;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\Grammar\PdoMySqlGrammar;
use Tobento\Service\Storage\Grammar\GrammarException;
use Tobento\Service\Storage\Tables\Tables;

/**
 * PdoMySqlGrammarSelectOrderTest
 */
class PdoMySqlGrammarSelectOrderTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testOrder()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'title',
                    'direction' => 'asc',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products` ORDER BY `title` ASC',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testOrderWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'title',
                    'direction' => 'asc',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`sku`,`p`.`price` FROM `products` as `p` ORDER BY `p`.`title` ASC',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }

    public function testOrderWithDescDirection()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'title',
                    'direction' => 'desc',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products` ORDER BY `title` DESC',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testOrderWithInvalidDirectionUsesDesc()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'title',
                    'direction' => 'invalid',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products` ORDER BY `title` DESC',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}