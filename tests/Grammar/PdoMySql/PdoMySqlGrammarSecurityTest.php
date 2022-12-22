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
use Tobento\Service\Storage\Query\JoinClause;

/**
 * PdoMySqlGrammarSecurityTest
 */
class PdoMySqlGrammarSecurityTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testThrowsGrammarExceptionIfInvalidTable()
    {
        $this->expectException(GrammarException::class);
        
        $grammar = (new PdoMySqlGrammar($this->tables))->table('unknown');
        $grammar->getStatement();      
    }
    
    public function testTableIgnoresInvalidTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products as in%valid')->select(['sku']);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );    
    }    
    
    public function testSelectMethodSkipsInvalidColumn()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select(['sku', 'invalid']);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );  
    }
    
    public function testSelectMethodIgnoresInvalidColumnAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select(['sku', 'title as in%valid']);
        
        $this->assertSame(
            ['SELECT `sku`,`title` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );  
    }
    
    public function testSelectMethodWithJsonPathIgnoresInvalidColumnAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select(['sku', 'title->en as in%valid']);
        
        $this->assertSame(
            ['SELECT `sku`,json_unquote(json_extract(`title`, \'$."en"\')) FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );  
    }    
    
    public function testSelectMethodIgnoresInvalidJsonPath()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select(['sku->in%valid']);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testJoinUsesInnerJoinIfInvalidDirection()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'invalid')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products` INNER JOIN `products_lg` on `id` = `product_id`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }    
    
    public function testInnerJoinSkipsJoinIfInvalidTable()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'invalid',
                    'inner')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testInnerJoinSkipsJoinIfInvalidFirstColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('invalid', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testInnerJoinSkipsJoinIfInvalidSecondColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'inner')
                )->on('id', '=', 'invalid')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testLeftJoinSkipsJoinIfInvalidTable()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'invalid',
                    'left')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testLeftJoinSkipsJoinIfInvalidFirstColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'left')
                )->on('invalid', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testLeftJoinSkipsJoinIfInvalidSecondColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'left')
                )->on('id', '=', 'invalid')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testRightJoinSkipsJoinIfInvalidTable()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'invalid',
                    'right')
                )->on('id', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testRightJoinSkipsJoinIfInvalidFirstColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'right')
                )->on('invalid', '=', 'product_id')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testRightJoinSkipsJoinIfInvalidSecondColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->joins([
                (new JoinClause(
                    $storage,
                    'products_lg',
                    'right')
                )->on('id', '=', 'invalid')
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereBaseSkipsClauseIfInvalidColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'invalid',
                    'value' => '55',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereColumnSkipsClauseIfInvalidColumn()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'invalid',
                    'value' => 'title',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereColumnSkipsClauseIfInvalidValue()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'sku',
                    'value' => 'invalid',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereColumnIgnoresColumnAlias()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'sku as s',
                    'value' => 'title',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products` WHERE `sku` = `title`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereColumnIgnoresColumnWithInvalidJsonPath()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'sku->in%valid',
                    'value' => 'title',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products` WHERE `sku` = `title`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereColumnIgnoresValueColumnAlias()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'sku',
                    'value' => 'title as s',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products` WHERE `sku` = `title`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
    
    public function testWhereColumnIgnoresValueColumnWithInvalidJsonPath()
    {
        $storage = new PdoMySqlStorageMock($this->tables);
        
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->select(['sku'])
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'sku',
                    'value' => 'title->in%valid',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            ['SELECT `sku` FROM `products` WHERE `sku` = `title`', []],
            [$grammar->getStatement(), $grammar->getBindings()]
        );
    }
}