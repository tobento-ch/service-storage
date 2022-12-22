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

namespace Tobento\Service\Storage\Test\Grammar\PdoMariaDb;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\Grammar\PdoMariaDbGrammar;
use Tobento\Service\Storage\Grammar\GrammarException;
use Tobento\Service\Storage\Tables\Tables;

/**
 * PdoMariaDbGrammarSelectJsonOrderTest
 */
class PdoMariaDbGrammarSelectJsonOrderTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testOrder()
    {
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'data->color',
                    'direction' => 'asc',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products` ORDER BY json_unquote(json_extract(`data`, \'$."color"\')) ASC',
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
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products p')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'data->color',
                    'direction' => 'asc',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`sku`,`p`.`price` FROM `products` as `p` ORDER BY json_unquote(json_extract(`p`.`data`, \'$."color"\')) ASC',
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
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'data->color',
                    'direction' => 'desc',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products` ORDER BY json_unquote(json_extract(`data`, \'$."color"\')) DESC',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    } 
    
    public function testOrderWithJsonAndInjectedSqlUsesColumnOnly()
    {
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->orders([
                [
                    'column' => 'title->"%27))%23injectedSQL',
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
}