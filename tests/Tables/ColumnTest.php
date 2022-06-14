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
use Tobento\Service\Storage\Tables\Column;
use Tobento\Service\Storage\Tables\ColumnInterface;

/**
 * ColumnTest tests
 */
class ColumnTest extends TestCase
{
    public function testColumnIsInstanceofColumnInterface()
    {
        $this->assertInstanceof(
            ColumnInterface::class,
            new Column('title')
        );        
    }
    
    public function testWithRawName()
    { 
        $c = new Column('title');
        
        $this->assertSame(
            ['title', null, 'title', null, null],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
    }
    
    public function testNameWithAlias()
    {
        $c = new Column('title as t');
        
        $this->assertSame(
            ['title as t', null, 'title', 't', null],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
        
        $c = new Column('title t');
        
        $this->assertSame(
            ['title t', null, 'title', 't', null],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );        
    }
    
    public function testWithJsonDelimiter()
    {
        $c = new Column('title->en');

        $this->assertSame(
            ['title->en', null, 'title', null, ['en']],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
    }
    
    public function testNameWithAliasJsonDelimiter()
    {
        $c = new Column('title->en as t');
        
        $this->assertSame(
            ['title->en as t', null, 'title', 't', ['en']],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
    }
    
    public function testWithTableAliasMethod()
    {
        $c = new Column('title->en as t');
        
        $n = $c->withTableAlias('tb');
        
        $this->assertFalse($c === $n);
        
        $this->assertSame(
            ['tb.title->en as t', 'tb', 'title', 't', ['en']],
            [$n->column(), $n->tableAlias(), $n->name(), $n->alias(), $n->jsonSegments()]
        );
    }
    
    public function testWithAliasMethod()
    {
        $c = new Column('title as t');
        
        $n = $c->withAlias('n');
        
        $this->assertFalse($c === $n);
        
        $this->assertSame(
            ['title as n', null, 'title', 'n', null],
            [$n->column(), $n->tableAlias(), $n->name(), $n->alias(), $n->jsonSegments()]
        );
    }

    public function testWithJsonSegmentsMethod()
    {
        $c = new Column('title');
        
        $n = $c->withJsonSegments(['de']);
        
        $this->assertFalse($c === $n);
        
        $this->assertSame(
            ['title->de', null, 'title', null, ['de']],
            [$n->column(), $n->tableAlias(), $n->name(), $n->alias(), $n->jsonSegments()]
        );
    }
    
    public function testWithJsonSegmentsMethodWithNull()
    {
        $c = new Column('title->en');
        
        $n = $c->withJsonSegments(null);
        
        $this->assertFalse($c === $n);
        
        $this->assertSame(
            ['title', null, 'title', null, null],
            [$n->column(), $n->tableAlias(), $n->name(), $n->alias(), $n->jsonSegments()]
        );
    }

    public function testInvalidAlias()
    {
        $c = new Column('title as in%re');
        
        $this->assertSame(
            ['title as in%re', null, 'title', null, null],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
    }
    
    public function testInvalidTableAlias()
    {
        $c = new Column('title');
        $c = $c->withTableAlias('t%b');
        
        $this->assertSame(
            ['title', null, 'title', null, null],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
    }    
    
    public function testInvalidJsonSegments()
    {
        $c = new Column('title->in%re');
        
        $this->assertSame(
            ['title->in%re', null, 'title', null, null],
            [$c->column(), $c->tableAlias(), $c->name(), $c->alias(), $c->jsonSegments()]
        );
    }
}