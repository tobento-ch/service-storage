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
 * PdoMySqlGrammarDeleteTest
 */
class PdoMySqlGrammarDeleteTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testDelete()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->delete(return: null);
        
        $this->assertSame(
            [
                'DELETE FROM `products`',
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
    
    public function testDeleteWithIgnoresTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->delete(return: null);
        
        $this->assertSame(
            [
                'DELETE FROM `products`',
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
    
    public function testDeleteWithWhere()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->wheres([
                [
                    'type' => 'Base',
                    'column' => 'id',
                    'value' => 5,
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ])
            ->delete(return: null);
        
        $this->assertSame(
            [
                'DELETE FROM `products` WHERE `id` = ?',
                [
                    0 => 5,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testDeleteReturning()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->delete();
        
        $this->assertSame(
            [
                'DELETE FROM `products` RETURNING `id`,`sku`,`price`,`title`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}