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

namespace Tobento\Service\Storage\Test\Tables;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\Tables\TableFactory;
use Tobento\Service\Storage\Tables\TableFactoryInterface;
use Tobento\Service\Storage\Tables\TableInterface;

/**
 * TableFactoryTest
 */
class TableFactoryTest extends TestCase
{
    public function testThatIsInstanceofTableFactoryInterface()
    {
        $this->assertInstanceof(
            TableFactoryInterface::class,
            new TableFactory()
        );        
    }
    
    public function testCreateTableMethod()
    {
        $this->assertInstanceof(
            TableInterface::class,
            (new TableFactory())->createTable(table: 'users', columns: [], primaryKey: 'id')
        );
    }    
}