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

namespace Tobento\Service\Storage\Test\Grammar\PdoMySql;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\Grammar\PdoMySqlGrammar;
use Tobento\Service\Storage\Grammar\GrammarException;
use Tobento\Service\Storage\Tables\Tables;

/**
 * PdoMySqlGrammarSelectGroupByTest
 */
class PdoMySqlGrammarSelectGroupByTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testGroupBy()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku', 'price'])
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'BASE',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products` GROUP BY `title`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select(['sku', 'price'])
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'BASE',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`sku`,`p`.`price` FROM `products` as `p` GROUP BY `p`.`title`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByMulitple()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select(['sku', 'price'])
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'BASE',
                ],
                [
                    'column' => 'sku',
                    'type' => 'BASE',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`sku`,`p`.`price` FROM `products` as `p` GROUP BY `p`.`title`, `p`.`sku`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByHaving()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'Base',
                ],
            ])
            ->havings([
                [
                    'column' => 'title',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Foo',
                    'boolean' => 'and',
                ],
            ]);            
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` GROUP BY `title` HAVING `title` = ?',
                ['Foo'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByHavingWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'Base',
                ],
            ])
            ->havings([
                [
                    'column' => 'title',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Foo',
                    'boolean' => 'and',
                ],
            ]);            
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` GROUP BY `p`.`title` HAVING `p`.`title` = ?',
                ['Foo'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByHavingAndHaving()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'Base',
                ],
            ])
            ->havings([
                [
                    'column' => 'title',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Foo',
                    'boolean' => 'and',
                ],
                [
                    'column' => 'sku',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Bar',
                    'boolean' => 'and',
                ],                
            ]);        
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` GROUP BY `title` HAVING `title` = ? and `sku` = ?',
                ['Foo', 'Bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }

    public function testGroupByHavingOrHaving()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'Base',
                ],
            ])
            ->havings([
                [
                    'column' => 'title',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Foo',
                    'boolean' => 'and',
                ],
                [
                    'column' => 'sku',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Bar',
                    'boolean' => 'or',
                ],                
            ]);        
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` GROUP BY `title` HAVING `title` = ? or `sku` = ?',
                ['Foo', 'Bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByHavingBetween()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'Base',
                ],
            ])
            ->havings([
                [
                    'column' => 'title',
                    'type' => 'Between',
                    'operator' => '=',
                    'not' => false,
                    'value' => [2,4],
                    'boolean' => 'and',
                ],
            ]);            
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` GROUP BY `title` HAVING `title` between ? and ?',
                [2,4],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testGroupByHavingBetweenWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->groups([
                [
                    'column' => 'title',
                    'type' => 'Base',
                ],
            ])
            ->havings([
                [
                    'column' => 'title',
                    'type' => 'Between',
                    'operator' => '=',
                    'not' => false,
                    'value' => [2,4],
                    'boolean' => 'and',
                ],
            ]);            
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` GROUP BY `p`.`title` HAVING `p`.`title` between ? and ?',
                [2,4],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }    
}