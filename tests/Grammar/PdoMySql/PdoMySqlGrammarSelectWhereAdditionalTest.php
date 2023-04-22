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
use Tobento\Service\Storage\Test\Mock\PdoMySqlStorageMock;
use Tobento\Service\Storage\Query\SubQueryWhere;

/**
 * PdoMySqlGrammarSelectWhereAdditionalTest
 */
class PdoMySqlGrammarSelectWhereAdditionalTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testWhereIn()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'In',
                    'column' => 'title',
                    'value' => ['blue', 'red'],
                    'operator' => 'IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `title` IN (?, ?)',
                ['blue', 'red'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereInWithEmptyValues()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'In',
                    'column' => 'title',
                    'value' => [],
                    'operator' => 'IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE 0 = 1',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereInWithTableAlias()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'In',
                    'column' => 'title',
                    'value' => ['blue', 'red'],
                    'operator' => 'IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`title` IN (?, ?)',
                ['blue', 'red'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotIn()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'In',
                    'column' => 'title',
                    'value' => ['blue', 'red'],
                    'operator' => 'NOT IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `title` NOT IN (?, ?)',
                ['blue', 'red'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotInWithTableAlias()
    {        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'In',
                    'column' => 'title',
                    'value' => ['blue', 'red'],
                    'operator' => 'NOT IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`title` NOT IN (?, ?)',
                ['blue', 'red'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNull()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Null',
                    'column' => 'title',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `title` is null',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNullWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Null',
                    'column' => 'title',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`title` is null',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotNull()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'NotNull',
                    'column' => 'title',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `title` is not null',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotNullWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'NotNull',
                    'column' => 'title',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`title` is not null',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereBetween()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Between',
                    'column' => 'title',
                    'value' => [1, 100],
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `title` between ? and ?',
                [
                    0 => 1,
                    1 => 100,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereBetweenEmptyArray()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Between',
                    'column' => 'title',
                    'value' => [],
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE 0 = 1',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereBetweenWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Between',
                    'column' => 'title',
                    'value' => [1, 100],
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`title` between ? and ?',
                [
                    0 => 1,
                    1 => 100,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotBetween()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'NotBetween',
                    'column' => 'title',
                    'value' => [1, 100],
                    'operator' => '!=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `title` not between ? and ?',
                [
                    0 => 1,
                    1 => 100,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotBetweenWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'NotBetween',
                    'column' => 'title',
                    'value' => [1, 100],
                    'operator' => '!=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`title` not between ? and ?',
                [
                    0 => 1,
                    1 => 100,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }     
}