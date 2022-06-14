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
use Tobento\Service\Storage\Tables\ColumnFactory;
use Tobento\Service\Storage\Tables\ColumnFactoryInterface;
use Tobento\Service\Storage\Tables\ColumnInterface;

/**
 * ColumnFactoryTest
 */
class ColumnFactoryTest extends TestCase
{
    public function testThatIsInstanceofColumnFactoryInterface()
    {
        $this->assertInstanceof(
            ColumnFactoryInterface::class,
            new ColumnFactory()
        );        
    }
    
    public function testCreateColumnMethod()
    {
        $this->assertInstanceof(
            ColumnInterface::class,
            (new ColumnFactory())->createColumn('title')
        );
    }
}