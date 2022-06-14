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
 * PdoMySqlGrammarSelectLimitTest tests
 */
class PdoMySqlGrammarSelectLimitTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testLimit()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select()->limit([1]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` Limit 0, 1',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testLimitAndOffset()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))->table('products')->select()->limit([2, 10]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` Limit 10, 2',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
}