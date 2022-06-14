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
 * PdoMySqlGrammarSelectJsonTest tests
 */
class PdoMySqlGrammarSelectJsonTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'options'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }

    public function testSelectJsonColumn()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['data->foo']);
        
        $this->assertSame(
            [
                'SELECT json_unquote(json_extract(`data`, \'$."foo"\')) FROM `products`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testSelectJsonColumnWithAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['data->foo as foo']);
        
        $this->assertSame(
            [
                'SELECT json_unquote(json_extract(`data`, \'$."foo"\')) as `foo` FROM `products`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testSelectJsonColumnWithAliasAndTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select(['data->foo as foo']);
        
        $this->assertSame(
            [
                'SELECT json_unquote(json_extract(`p`.`data`, \'$."foo"\')) as `foo` FROM `products` as `p`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
    
    public function testJsonContainsWithOneDepth()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_contains(`data`, ?, \'$."color"\')',
                [
                    0 => '"blue"'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testJsonContainsWithDeeperDepth()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data->options->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_contains(`data`, ?, \'$."options"."color"\')',
                [
                    0 => '"blue"'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testJsonContainsWithArrayValue()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data->color',
                    'value' => ['blue', 5, 'red'],
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_contains(`data`, ?, \'$."color"\')',
                [
                    0 => '["blue",5,"red"]'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testJsonContainsWithNullValue()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_contains(`data`, ?, \'$."color"\')',
                [
                    0 => 'null'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testJsonContainsWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_contains(`p`.`data`, ?, \'$."color"\')',
                [
                    0 => '"blue"'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testJsonContainsWithoutDelimiter()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_contains(`data`, ?)',
                [
                    0 => '"blue"'
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testJsonContainsOrJsonContains()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContains',
                    'column' => 'data->color',
                    'value' => 'blue',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'JsonContains',
                    'column' => 'data->foo',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'or',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_contains(`p`.`data`, ?, \'$."color"\') or json_contains(`p`.`data`, ?, \'$."foo"\')',
                [
                    0 => '"blue"',
                    1 => 'null',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }

    public function testJsonContainsKey()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContainsKey',
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE ifnull(json_contains_path(`data`, \'one\', \'$."color"\'), 0)',
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
    
    public function testJsonContainsKeyWithoutDelimiterSkips()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContainsKey',
                    'column' => 'data',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products`',
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
        
    public function testJsonContainsKeyWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContainsKey',
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE ifnull(json_contains_path(`p`.`data`, \'one\', \'$."color"\'), 0)',
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
    
    public function testJsonContainsKeyOrJsonContainsKey()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonContainsKey',
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'JsonContainsKey',
                    'column' => 'data->color',
                    'value' => null,
                    'operator' => '=',
                    'boolean' => 'or',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE ifnull(json_contains_path(`data`, \'one\', \'$."color"\'), 0) or ifnull(json_contains_path(`data`, \'one\', \'$."color"\'), 0)',
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
    
    public function testJsonLength()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonLength',
                    'column' => 'data->color',
                    'value' => 2,
                    'operator' => '>',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_length(`data`, \'$."color"\') > ?',
                [
                    0 => 2
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testJsonLengthWithoutJsonDelimiter()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonLength',
                    'column' => 'data',
                    'value' => 2,
                    'operator' => '>',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_length(`data`) > ?',
                [
                    0 => 2
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }    
    
    public function testJsonLengthWithTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonLength',
                    'column' => 'data->color',
                    'value' => 2,
                    'operator' => '>',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`title`,`p`.`data` FROM `products` as `p` WHERE json_length(`p`.`data`, \'$."color"\') > ?',
                [
                    0 => 2
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testJsonLengthOrJsonLength()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'JsonLength',
                    'column' => 'data->color',
                    'value' => 2,
                    'operator' => '>',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'JsonLength',
                    'column' => 'data->foo',
                    'value' => 1,
                    'operator' => '>',
                    'boolean' => 'or',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`title`,`data` FROM `products` WHERE json_length(`data`, \'$."color"\') > ? or json_length(`data`, \'$."foo"\') > ?',
                [
                    0 => 2,
                    1 => 1,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }    
}