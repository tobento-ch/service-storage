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
 * PdoMySqlGrammarUpdateJsonTest
 */
class PdoMySqlGrammarUpdateJsonTest extends TestCase
{
    protected $tables;
    
    public function setUp(): void
    {
        $this->tables = (new Tables())
                ->add('products', ['id', 'sku', 'price', 'title', 'data'], 'id')
                ->add('products_lg', ['product_id', 'language_id', 'title', 'description'])
                ->add('categories', ['id', 'product_id', 'title', 'description']);
    }
    
    public function testUpdate()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->update([
                'sku' => 'Sku',
                'data->color' => 'blue',
            ]);
        
        $this->assertSame(
            [
                'UPDATE `products` SET `sku` = ?, `data` = json_set(`data`, \'$."color"\', ?)',
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
    
    public function testUpdateWithWhere()
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
            ->update([
                'sku' => 'Sku',
                'data->color' => 'blue',
            ]);
        
        $this->assertSame(
            [
                'UPDATE `products` SET `sku` = ?, `data` = json_set(`data`, \'$."color"\', ?) WHERE `id` = ?',
                [
                    0 => 'Sku',
                    1 => 'blue',
                    2 => 5,
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );
    }
    
    public function testUpdateWithArrayValueDoesCastAsJson()
    {
        $grammar = (new PdoMySqlGrammar($this->tables))
            ->table('products')
            ->update([
                'sku' => 'Sku',
                'data->color' => ['blue'],
            ]);
        
        $this->assertSame(
            [
                'UPDATE `products` SET `sku` = ?, `data` = json_merge_patch(IF(JSON_TYPE(`data`) is NULL, "{}", `data`), ?)',
                [
                    0 => 'Sku',
                    1 => '{"color":["blue"]}',
                ],
            ],
            [
                $grammar->getStatement(),
                $grammar->getBindings()
            ]
        );   
    }
}