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
use Tobento\Service\Storage\Test\Mock\PdoMariaDbStorageMock;
use Tobento\Service\Storage\Query\JoinClause;

/**
 * PdoMariaDbGrammarSelectJoinTest tests
 */
class PdoMariaDbGrammarSelectJoinTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }

    public function testInnerJoin()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` INNER JOIN `products_lg` on `id` = `product_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testLeftJoin()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'left')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` LEFT JOIN `products_lg` on `id` = `product_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testRightJoin()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'right')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` RIGHT JOIN `products_lg` on `id` = `product_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testMultipleJoins()
    {        
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'product_id'),
                (new JoinClause(
                    $storage,
                    'categories',
                    'inner')
                )->on('id', '=', 'product_id')                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` INNER JOIN `products_lg` on `id` = `product_id` INNER JOIN `categories` on `id` = `product_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInnerJoinWithInvalidColumnSkipsJoin()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'invalid')
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }

    public function testInnerJoinWithOrOnClause()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'product_id')->orOn('id', '=', 'language_id')
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` INNER JOIN `products_lg` on `id` = `product_id` or `id` = `language_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInnerJoinWithWhereClause()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'product_id')->where('id', '>', 2)
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` INNER JOIN `products_lg` on `id` = `product_id` and `id` > ?',
                [2],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInnerJoinWithWhereOrWhereClause()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'product_id')->where('id', '>', 2)->orWhere('sku', 'like', '%pe%')
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title`,`product_id`,`language_id`,`description` FROM `products` INNER JOIN `products_lg` on `id` = `product_id` and `id` > ? or `sku` like ?',
                [2, '%pe%'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
}