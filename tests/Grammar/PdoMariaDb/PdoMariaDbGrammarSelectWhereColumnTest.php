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
 * PdoMariaDbGrammarSelectWhereColumnTest tests
 */
class PdoMariaDbGrammarSelectWhereColumnTest extends TestCase
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
                    'type' => 'Column',
                    'column' => 'sku',
                    'value' => 'title',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `id`,`sku`,`price`,`title` FROM `products` WHERE `sku` = `title`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
    
    public function testWhereEqualOperatorWithTableAlias()
    {        
        $grammar = (new PdoMariaDbGrammar($this->tables))
            ->table('products p')
            ->select()
            ->wheres([
                [
                    'type' => 'Column',
                    'column' => 'sku',
                    'value' => 'title',
                    'operator' => '=',
                    'boolean' => 'and',
                ],
            ]);
        
        $this->assertSame(
            [
                'SELECT `p`.`id`,`p`.`sku`,`p`.`price`,`p`.`title` FROM `products` as `p` WHERE `p`.`sku` = `p`.`title`',
                [],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }    
}