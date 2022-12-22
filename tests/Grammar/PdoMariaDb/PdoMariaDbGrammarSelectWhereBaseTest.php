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
use Tobento\Service\Storage\Query\SubQueryWhere;

/**
 * PdoMariaDbGrammarSelectWhereBaseTest tests
 */
class PdoMariaDbGrammarSelectWhereBaseTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testWhereEqualOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
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
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` = ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotEqualOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '!=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` != ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '<>',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` <> ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );         
    }
    
    public function testWhereGreaterOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '>',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` > ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    

    public function testWhereGreaterOrEqualOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '>=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` >= ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }

    public function testWhereSmallerOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '<',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` < ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    } 
    
    public function testWhereSmallerOrEqualOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '<=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` <= ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereSpaceshipOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '<=>',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` <=> ?',
                ['1'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereLikeOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => 'foo',
                    'operator' => 'like',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` like ?',
                ['foo'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereLikeAnyPositionOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '%foo%',
                    'operator' => 'like',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` like ?',
                ['%foo%'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    

    public function testWhereNotLikeOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => 'foo',
                    'operator' => 'not like',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` not like ?',
                ['foo'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereNotLikeAnyPositionOperator()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '%foo%',
                    'operator' => 'not like',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `id` not like ?',
                ['%foo%'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWithInvalidTypeSkipsWhere()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Invalid',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
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
    
    public function testWithInvalidColumnSkipsWhere()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'unknown',
                    'value' => '1',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
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
    
    public function testWithInvalidOperatorSkipsWhere()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '//',
                    'boolean' => 'and',
                ],
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
    
    public function testWithInvalidBooleanDoesSkipsWhere()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => '1',
                    'operator' => '=',
                    'boolean' => 'invalid',
                ],
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
    
    public function testWithMultiple()
    {    
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'sku',
                    'value' => 'foo',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'Base',
                    'column' => 'title',
                    'value' => 'bar',
                    'operator' => '=',
                    'boolean' => 'and',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `sku` = ? and `title` = ?',
                ['foo', 'bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWithMultipleBooleanOr()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'sku',
                    'value' => 'foo',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
                [
                    'type' => 'Base',
                    'column' => 'title',
                    'value' => 'bar',
                    'operator' => '=',
                    'boolean' => 'or',
                ],                
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `sku` = ? or `title` = ?',
                ['foo', 'bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWithColumnSubQuery()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
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
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE (`sku` = ? or `sku` = ?)',
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
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => new SubQueryWhere(function($query) {
                        // if table is added it is a subquery, otherwise a nested query
                        $query->select('sku')
                              ->table('products')
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
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE (SELECT `sku` FROM `products` WHERE `sku` = ? or `sku` = ?) = ?',
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
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'sku',
                    'value' => new SubQueryWhere(function($query) {
                        $query->select('sku')
                              ->table('products') // table is required, otherwise it gets not assigned
                              ->where('sku', '=', 'foo')
                              ->orWhere('sku', '=', 'bar');
                    }, $storage),
                    'operator' => '=',
                    'boolean' => 'and',
                ],               
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `sku` = (SELECT `sku` FROM `products` WHERE `sku` = ? or `sku` = ?)',
                ['foo', 'bar'],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }

    public function testWithValueSubQueryWithoutTableIgnoresWhere()
    {
        $storage = new PdoMariaDbStorageMock($this->tables);
        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products')
            ->select()
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'sku',
                    'value' => new SubQueryWhere(function($query) {
                        $query->select('sku')
                              ->where('sku', '=', 'foo')
                              ->orWhere('sku', '=', 'bar');
                    }, $storage),
                    'operator' => '=',
                    'boolean' => 'and',
                ],               
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
}