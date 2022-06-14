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
 * PdoMySqlGrammarSelectTest tests
 */
class PdoMySqlGrammarSelectTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }

    public function testThrowsGrammarExceptionWithoutTable()
    {
        $this->expectException(GrammarException::class);
        
        $grammar = (new PdoMySqlGrammar($this->tables));
        $grammar->getStatement();      
    }
    
    public function testThrowsGrammarExceptionIfInvalidTable()
    {
        $this->expectException(GrammarException::class);
        
        $grammar = (new PdoMySqlGrammar($this->tables))->table('unknown');
        $grammar->getStatement();      
    }
    
    public function testThrowsGrammarExceptionWithoutAnyActionMethod()
    {
        // action methods are: select, insert, update, delete
        
        $this->expectException(GrammarException::class);
        
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products');
        $grammar->getStatement();      
    }    
    
    public function testEmptySelectAssignsAllColumns()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select();
        
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
    
    public function testSelectWithSpecifiedColumns()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select(['sku', 'price']);
        
        $this->assertSame(
            [
                'SELECT `sku`,`price` FROM `products`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }   
    
    public function testSelectWithStringStatement()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select('count(*) as aggregate');
        
        $this->assertSame(
            [
                'SELECT count(*) as aggregate FROM `products`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}