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
use Tobento\Service\Storage\Test\Mock\PdoMySqlStorageMock;
use Tobento\Service\Storage\Query\SubQueryWhere;

/**
 * PdoMySqlGrammarSelectWhereBaseAliasedTest tests
 */
class PdoMySqlGrammarSelectWhereBaseAliasedTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testWhere()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'p.id',
                    'value' => '1',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`id` = ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWithInvalidColumnAliasAutoResolvesAlias()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'a.id',
                    'value' => '1',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`id` = ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
    
    public function testWithUnaliasedColumnShouldGetAliased()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`id` = ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
    
    public function testWithColumnSubQuery()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => new SubQueryWhere(function($query) {
                        // if table is added it is a subquery, otherwise a nested query
                        $query->select('sku')
                              //->table('shop_products')
                              ->where('sku', '=', 'foo')
                              ->orWhere('sku', '=', 'bar');
                    }, $storage),
                    'value' => 'abc',
                    'operator' => '=',
                    'boolean' => 'and',
                ],               
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE (`p`.`sku` = ? or `p`.`sku` = ?)',
                ['foo', 'bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWithColumnSubQueryWithTable()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => new SubQueryWhere(function($query) {
                        // if table is added it is a subquery, otherwise a nested query
                        $query->select('sku')
                              ->table('products a')
                              ->where('sku', '=', 'bar')
                              ->orWhere('sku', '=', 'foo');
                    }, $storage),
                    'value' => 'abc',
                    'operator' => '=',
                    'boolean' => 'and',
                ],               
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE (SELECT `a`.`sku` FROM `products` as `a` WHERE `a`.`sku` = ? or `a`.`sku` = ?) = ?',
                ['abc', 'bar', 'foo'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWithValueSubQuery()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'sku',
                    'value' => new SubQueryWhere(function($query) {
                        $query->select('sku')
                              ->table('products p') // table is required, otherwise it gets not assigned
                              ->where('sku', '=', 'foo')
                              ->orWhere('sku', '=', 'bar');
                    }, $storage),
                    'operator' => '=',
                    'boolean' => 'and',
                ],               
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`sku` = (SELECT `p`.`sku` FROM `products` as `p` WHERE `p`.`sku` = ? or `p`.`sku` = ?)',
                ['foo', 'bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }   
}