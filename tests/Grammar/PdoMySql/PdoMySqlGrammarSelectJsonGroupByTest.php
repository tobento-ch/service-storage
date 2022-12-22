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
 * PdoMySqlGrammarSelectJsonGroupByTest
 */
class PdoMySqlGrammarSelectJsonGroupByTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description', 'options'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testGroupBy()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products_lg')
            ->select(['product_id'])
            ->groups([
                [
                    'column' => 'options->price',
                    'type' => 'BASE',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `product_id` FROM `products_lg` GROUP BY json_unquote(json_extract(`options`, \'$."price"\'))',
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
            ->table('products_lg p')
            ->select(['product_id'])
            ->groups([
                [
                    'column' => 'options->price',
                    'type' => 'BASE',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`product_id` FROM `products_lg` as `p` GROUP BY json_unquote(json_extract(`p`.`options`, \'$."price"\'))',
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
            ->table('products_lg')
            ->select(['product_id'])
            ->groups([
                [
                    'column' => 'options->price',
                    'type' => 'BASE',
                ],
            ])
            ->havings([
                [
                    'column' => 'options->price',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Foo',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `product_id` FROM `products_lg` GROUP BY json_unquote(json_extract(`options`, \'$."price"\')) HAVING json_unquote(json_extract(`options`, \'$."price"\')) = ?',
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
            ->table('products_lg p')
            ->select(['product_id'])
            ->groups([
                [
                    'column' => 'options->price',
                    'type' => 'BASE',
                ],
            ])
            ->havings([
                [
                    'column' => 'options->price',
                    'type' => 'Base',
                    'operator' => '=',
                    'value' => 'Foo',
                    'boolean' => 'and',
                ],
            ]);            
        
        $this->assertSame(
            [
                'SELECT `p`.`product_id` FROM `products_lg` as `p` GROUP BY json_unquote(json_extract(`p`.`options`, \'$."price"\')) HAVING json_unquote(json_extract(`p`.`options`, \'$."price"\')) = ?',
                ['Foo'],
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
            ->table('products_lg')
            ->select(['product_id'])
            ->groups([
                [
                    'column' => 'options->price',
                    'type' => 'BASE',
                ],
            ])
            ->havings([
                [
                    'column' => 'options->price',
                    'type' => 'Between',
                    'operator' => '=',
                    'not' => false,
                    'value' => [2,4],
                    'boolean' => 'and',
                ],
            ]);  
        
        $this->assertSame(
            [
                'SELECT `product_id` FROM `products_lg` GROUP BY json_unquote(json_extract(`options`, \'$."price"\')) HAVING json_unquote(json_extract(`options`, \'$."price"\')) between ? and ?',
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
            ->table('products_lg p')
            ->select(['product_id'])
            ->groups([
                [
                    'column' => 'options->price',
                    'type' => 'BASE',
                ],
            ])
            ->havings([
                [
                    'column' => 'options->price',
                    'type' => 'Between',
                    'operator' => '=',
                    'not' => false,
                    'value' => [2,4],
                    'boolean' => 'and',
                ],
            ]);  
        
        $this->assertSame(
            [
                'SELECT `p`.`product_id` FROM `products_lg` as `p` GROUP BY json_unquote(json_extract(`p`.`options`, \'$."price"\')) HAVING json_unquote(json_extract(`p`.`options`, \'$."price"\')) between ? and ?',
                [2,4],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }    
}