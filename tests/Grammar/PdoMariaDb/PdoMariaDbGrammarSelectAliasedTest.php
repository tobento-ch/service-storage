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

/**
 * PdoMariaDbGrammarSelectAliasedTest tests
 */
class PdoMariaDbGrammarSelectAliasedTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    } 
    
    public function testEmptySelectAssignsAllColumns()
    {
        $grammar = (new PdoMariaDbGrammar($this->tables))->table('products p')->select();
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testSelectWithSpecifiedUnaliasedColumnsShouldAliasColumns()
    {
        $grammar = (new PdoMariaDbGrammar($this->tables))->table('products p')->select(['sku', 'price']);
        
        $this->assertSame(
            [
                'SELECT `p`.`sku`,`p`.`price` FROM `products` as `p`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}