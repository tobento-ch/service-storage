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
 * PdoMySqlGrammarInsertTest
 */
class PdoMySqlGrammarInsertTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testInsert()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->insert([
                'sku' => 'Sku',
                'title' => 'Title',
            ], return: null);
        
        $this->assertSame(
            [
                'INSERT INTO `products` (`sku`,`title`) VALUES (?, ?)',
                [
                    0 => 'Sku',
                    1 => 'Title',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInsertWithReturning()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->insert([
                'sku' => 'Sku',
                'title' => 'Title',
            ]);
        
        $this->assertSame(
            [
                'INSERT INTO `products` (`sku`,`title`) VALUES (?, ?) RETURNING `id`,`sku`,`price`,`title`',
                [
                    0 => 'Sku',
                    1 => 'Title',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInsertWithReturningSpecific()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->insert([
                'sku' => 'Sku',
                'title' => 'Title',
            ], return: ['id']);
        
        $this->assertSame(
            [
                'INSERT INTO `products` (`sku`,`title`) VALUES (?, ?) RETURNING `id`',
                [
                    0 => 'Sku',
                    1 => 'Title',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testInsertWithReturningSpecificReturnOnlyValid()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->insert([
                'sku' => 'Sku',
                'title' => 'Title',
            ], return: ['id', 'foo']);
        
        $this->assertSame(
            [
                'INSERT INTO `products` (`sku`,`title`) VALUES (?, ?) RETURNING `id`',
                [
                    0 => 'Sku',
                    1 => 'Title',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
    
    public function testInsertWithIgnoresTableAlias()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products p')
            ->insert([
                'title' => 'Title',
            ], return: null);
        
        $this->assertSame(
            [
                'INSERT INTO `products` (`title`) VALUES (?)',
                [
                    0 => 'Title',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
}