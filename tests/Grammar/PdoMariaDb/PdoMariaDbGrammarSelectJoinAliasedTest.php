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
 * PdoMariaDbGrammarSelectJoinAliasedTest tests
 */
class PdoMariaDbGrammarSelectJoinAliasedTest extends TestCase
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
            ->table('products p')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg pl',
                    'inner')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title`,`pl`.`product_id`,`pl`.`language_id`,`pl`.`title`,`pl`.`description` FROM `products` as `p` INNER JOIN `products_lg` as `pl` on `p`.`id` = `pl`.`product_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInnerJoinWithInvalidAliasedColumnAutoResolvesAlias()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products p')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg pl',
                    'inner')
                )->on('pl.id', '=', 'pl.product_id') // pl.id should become p.id
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title`,`pl`.`product_id`,`pl`.`language_id`,`pl`.`title`,`pl`.`description` FROM `products` as `p` INNER JOIN `products_lg` as `pl` on `p`.`id` = `pl`.`product_id`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInnerJoinWithoutJoinedTableAliasAutoResolvesAliases()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products p')
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
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title`,`product_id`,`language_id`,`title`,`description` FROM `products` as `p` INNER JOIN `products_lg` on `p`.`id` = `product_id`',
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
            ->table('products p')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg pl',
                    'inner')
                )->on('id', '=', 'product_id')->orOn('id', '=', 'language_id')
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title`,`pl`.`product_id`,`pl`.`language_id`,`pl`.`title`,`pl`.`description` FROM `products` as `p` INNER JOIN `products_lg` as `pl` on `p`.`id` = `pl`.`product_id` or `p`.`id` = `pl`.`language_id`',
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
            ->table('products p')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg pl',
                    'inner')
                )->on('id', '=', 'product_id')->where('id', '>', 2)
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title`,`pl`.`product_id`,`pl`.`language_id`,`pl`.`title`,`pl`.`description` FROM `products` as `p` INNER JOIN `products_lg` as `pl` on `p`.`id` = `pl`.`product_id` and `p`.`id` > ?',
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
            ->table('products p')
            ->select()
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg pl',
                    'inner')
                )->on('id', '=', 'product_id')->where('id', '>', 2)->orWhere('sku', 'like', '%pe%')
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title`,`pl`.`product_id`,`pl`.`language_id`,`pl`.`title`,`pl`.`description` FROM `products` as `p` INNER JOIN `products_lg` as `pl` on `p`.`id` = `pl`.`product_id` and `p`.`id` > ? or `p`.`sku` like ?',
                [2, '%pe%'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }     
}