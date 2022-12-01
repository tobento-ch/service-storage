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
 * PdoMySqlGrammarSelectJsonWhereTest tests
 */
class PdoMySqlGrammarSelectJsonWhereTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'options'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }

    public function testWhere()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) = ?',
                [
                    0 => 'blue'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_unquote(json_extract(`p`.`data`, \'$."color"\')) = ?',
                [
                    0 => 'blue'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
    
    public function testWhereOrWhere()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'Base',
                    'column' => 'data->color',
                    'value' => 'red',
                    'operator' => '=',
                    'boolean' => 'or',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) = ? or json_unquote(json_extract(`data`, \'$."color"\')) = ?',
                [
                    0 => 'blue',
                    1 => 'red',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereLike()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => 'like',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) like ?',
                [
                    0 => 'blue'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereLikeWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => 'like',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_unquote(json_extract(`p`.`data`, \'$."color"\')) like ?',
                [
                    0 => 'blue'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
    
    public function testWhereIn()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'In',
                    'column' => 'data->color',
                    'value' => ['blue', 'red'],
                    'operator' => 'IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) IN (?, ?)',
                [
                    0 => 'blue',
                    1 => 'red',
                ],
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
                    'column' => 'data->color',
                    'value' => ['blue', 'red'],
                    'operator' => 'IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_unquote(json_extract(`p`.`data`, \'$."color"\')) IN (?, ?)',
                [
                    0 => 'blue',
                    1 => 'red',
                ],
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
                    'column' => 'data->color',
                    'value' => ['blue', 'red'],
                    'operator' => 'NOT IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) NOT IN (?, ?)',
                [
                    0 => 'blue',
                    1 => 'red',
                ],
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
                    'column' => 'data->color',
                    'value' => ['blue', 'red'],
                    'operator' => 'NOT IN',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_unquote(json_extract(`p`.`data`, \'$."color"\')) NOT IN (?, ?)',
                [
                    0 => 'blue',
                    1 => 'red',
                ],
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
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE (json_unquote(json_extract(`data`, \'$."color"\')) is null or json_unquote(json_extract(`data`, \'$."color"\')) = \'NULL\')',
                [
                    //
                ],
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
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE (json_unquote(json_extract(`p`.`data`, \'$."color"\')) is null or json_unquote(json_extract(`p`.`data`, \'$."color"\')) = \'NULL\')',
                [
                    //
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNullMultiple()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Null',
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'Null',
                    'column' => 'data->material',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE (json_unquote(json_extract(`data`, \'$."color"\')) is null or json_unquote(json_extract(`data`, \'$."color"\')) = \'NULL\') and (json_unquote(json_extract(`data`, \'$."material"\')) is null or json_unquote(json_extract(`data`, \'$."material"\')) = \'NULL\')',
                [
                    //
                ],
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
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE (json_unquote(json_extract(`data`, \'$."color"\')) is not null AND json_unquote(json_extract(`data`, \'$."color"\')) != \'NULL\')',
                [
                    //
                ],
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
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE (json_unquote(json_extract(`p`.`data`, \'$."color"\')) is not null AND json_unquote(json_extract(`p`.`data`, \'$."color"\')) != \'NULL\')',
                [
                    //
                ],
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
                    'column' => 'data->color',
                    'value' => [1, 100],
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) between ? and ?',
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
    
    public function testWhereBetweenWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Between',
                    'column' => 'data->color',
                    'value' => [1, 100],
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_unquote(json_extract(`p`.`data`, \'$."color"\')) between ? and ?',
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
                    'column' => 'data->color',
                    'value' => [1, 100],
                    'operator' => '!=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_unquote(json_extract(`data`, \'$."color"\')) not between ? and ?',
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
                    'column' => 'data->color',
                    'value' => [1, 100],
                    'operator' => '!=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_unquote(json_extract(`p`.`data`, \'$."color"\')) not between ? and ?',
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
    
    public function testWhereColumn()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'title',
                    'value' => 'data->bar',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE `title` = json_unquote(json_extract(`data`, \'$."bar"\'))',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereColumnWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'title',
                    'value' => 'data->bar',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE `p`.`title` = json_unquote(json_extract(`p`.`data`, \'$."bar"\'))',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}