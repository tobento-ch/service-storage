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
use Tobento\Service\Storage\Tables\Table;
use Tobento\Service\Storage\Tables\TableInterface;
use Tobento\Service\Storage\Tables\ColumnInterface;
use Tobento\Service\Storage\Tables\Column;

/**
 * TableTest
 */
class TableTest extends TestCase
{
    public function testThatIsInstanceofTableInterface()
    {
        $this->assertInstanceof(
            TableInterface::class,
            new Table('users')
        );        
    }
    
    public function testTableRaw()
    {
        $t = new Table('users');
        
        $this->assertSame(
            ['users', 'users', null, []],
            [$t->table(), $t->name(), $t->alias(), $t->columns()]
        );
    }
    
    public function testTableWithAlias()
    {
        $t = new Table('users u');
        
        $this->assertSame(
            ['users u', 'users', 'u', []],
            [$t->table(), $t->name(), $t->alias(), $t->columns()]
        );
    }
    
    public function testTableWithAliasAs()
    {
        $t = new Table('users as u');
        
        $this->assertSame(
            ['users as u', 'users', 'u', []],
            [$t->table(), $t->name(), $t->alias(), $t->columns()]
        );
    }
    
    public function testTableInvalidWithAlias()
    {
        $t = new Table('users u%s');
        
        $this->assertSame(
            ['users u%s', 'users', null, []],
            [$t->table(), $t->name(), $t->alias(), $t->columns()]
        );
    }

    public function testWithColumns()
    {
        $t = new Table('users', ['id', 'name']);

        $this->assertSame(
            ['id', 'name'],
            array_keys($t->columns())
        );
    }
    
    public function testWithColumnsAndTableAlias()
    {
        $t = new Table('users u', ['id', 'name']);

        $this->assertSame(
            ['u.id', 'u.name'],
            array_keys($t->columns())
        );
    }
    
    public function testPrimaryKeyMethod()
    {
        $t = new Table('users');
        $this->assertSame(null, $t->primaryKey());
        
        $t = new Table('users', [], 'id');
        $this->assertSame('id', $t->primaryKey());        
    }

    public function testGetColumnMethod()
    {
        $t = new Table('users', ['id', 'name']);

        $this->assertInstanceof(
            ColumnInterface::class,
            $t->getColumn('name')
        );   
        
        $this->assertSame(
            null,
            $t->getColumn('invalid')
        );
    }
    
    public function testGetColumnMethodWithColumn()
    {
        $t = new Table('users', ['id', 'name']);
        $c = new Column('name');
        
        $this->assertInstanceof(
            ColumnInterface::class,
            $t->getColumn($c)
        );
        
        $this->assertFalse($c === $t->getColumn($c));
        
        $this->assertSame(
            ['id', 'name'],
            $t->getColumnNames()
        );        
    }  
    
    public function testWithColumnsMethod()
    {
        $t = new Table('users', ['id', 'name']);
        
        $n = $t->withColumns(['name', 'invalid']);
        
        $this->assertFalse($t === $n);
        
        $this->assertSame(
            ['name'],
            array_keys($n->columns())
        );
        
        $this->assertSame(
            ['name'],
            $n->getColumnNames()
        );        
    }
    
    public function testWithAliasMethod()
    {
        $t = new Table('users', ['id', 'name']);
        
        $n = $t->withAlias('u');
        
        $this->assertFalse($t === $n);
        
        $this->assertSame(
            ['u.id', 'u.name'],
            array_keys($n->columns())
        );
        
        $this->assertSame(
            ['users u', 'users', 'u'],
            [$n->table(), $n->name(), $n->alias()]
        );
        
        $this->assertSame(
            ['id', 'name'],
            $n->getColumnNames()
        );
    }    
}