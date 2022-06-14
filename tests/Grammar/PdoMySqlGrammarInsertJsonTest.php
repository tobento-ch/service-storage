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
 * PdoMySqlGrammarInsertJsonTest
 */
class PdoMySqlGrammarInsertJsonTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testInsertJsonPathIsIgnored()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->insert([
                'sku' => 'Sku',
                'data->color' => 'blue',
            ]);
        
        $this->assertSame(
            [
                'INSERT INTO `products` (`sku`,`data`) VALUES (?, ?)',
                [
                    0 => 'Sku',
                    1 => 'blue',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}