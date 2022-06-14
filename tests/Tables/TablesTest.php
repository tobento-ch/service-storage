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
use Tobento\Service\Storage\Tables\Tables;
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\Tables\TableInterface;
use Tobento\Service\Storage\Tables\ColumnInterface;
use Tobento\Service\Storage\Tables\Column;

/**
 * TablesTest
 */
class TablesTest extends TestCase
{
    public function testThatIsInstanceofTablesInterface()
    {
        $this->assertInstanceof(
            TablesInterface::class,
            new Tables()
        );
    }
    
    public function testAddMethod()
    {
        $tables = new Tables();
        $tables->add(table: 'users', columns: [], primaryKey: 'id');
        
        $this->assertInstanceof(
            TableInterface::class,
            $tables->getTable('users')
        );
    }
    
    public function testAddTableMethod()
    {
        $tables = new Tables();
        $tables->addTable(new Table('users'));
        
        $this->assertInstanceof(
            TableInterface::class,
            $tables->getTable('users')
        );
    }
    
    public function testRemoveTableMethod()
    {
        $tables = new Tables();
        $tables->add('users', []);
        
        $this->assertInstanceof(
            TableInterface::class,
            $tables->getTable('users')
        );
        
        $tables->removeTable(table: 'users');
        
        $this->assertSame(
            null,
            $tables->getTable('users')
        );        
    }
    
    public function testVerifyTableMethod()
    {
        $tables = new Tables();
        $tables->add('users', ['id', 'name']);

        $verifiedTable = $tables->verifyTable(table: 'users');
        
        $this->assertFalse($tables->getTable('users') === $verifiedTable);
        
        $this->assertInstanceof(
            TableInterface::class,
            $verifiedTable
        );    
    }
    
    public function testVerifyTableMethodUsesAliasFromVerifiedTable()
    {
        $tables = new Tables();
        $tables->add('users a', ['id', 'name']);
        
        $table = new Table('users n');
        
        $verifiedTable = $tables->verifyTable(table: $table);
        
        $this->assertSame(
            'n',
            $verifiedTable->alias()
        );    
    }
    
    public function testGetTableMethodWithTable()
    {
        $tables = new Tables();
        $tables->addTable(new Table('users a'));
        
        $table = new Table('users n');
        
        $this->assertInstanceof(
            TableInterface::class,
            $tables->getTable('users')
        );
    }
    
    public function testPrimaryKeyMethod()
    {
        $tables = new Tables();
        $tables->addTable(new Table('users', [], 'id'));
        $tables->addTable(new Table('products', []));
        
        $this->assertSame(
            'id',
            $tables->getTable('users')->primaryKey()
        );
        
        $this->assertSame(
            null,
            $tables->getTable('products')->primaryKey()
        );
    }
    
    public function testGetColumnMethod()
    {
        $tables = new Tables();
        $tables->add('users a', ['id', 'name']);
        $tables->add('products', ['id', 'title']);
        
        $this->assertSame(
            'a.name',
            $tables->getColumn('name')->column()
        );

        $this->assertSame(
            'id',
            $tables->getColumn('id')->column()
        );
        
        $this->assertSame(
            'title',
            $tables->getColumn('title')->column()
        );
        
        $this->assertSame(
            'title',
            $tables->getColumn(new Column('title as a'))->column()
        );        
    }
    
    public function testVerifyColumnMethod()
    {
        $tables = new Tables();
        $tables->add('users a', ['id', 'name']);
        $tables->add('products', ['id', 'title']);
        
        $this->assertSame(
            'a.name',
            $tables->verifyColumn('name')->column()
        );

        $this->assertSame(
            'id',
            $tables->verifyColumn('id')->column()
        );
        
        $this->assertSame(
            'title',
            $tables->verifyColumn('title')->column()
        );
        
        $this->assertSame(
            'title as a',
            $tables->verifyColumn(new Column('title as a'))->column()
        );        
    }
    
    public function testWithColumnsMethod()
    {
        $tables = new Tables();
        $tables->add('users a', ['id', 'name']);
        $tables->add('products', ['id', 'title']);
        
        $newTables = $tables->withColumns(['name', 'title']);
        
        $this->assertFalse($tables === $newTables);
        
        $this->assertSame(
            ['name'],
            $newTables->getTable('users')->getColumnNames()
        );
        
        $this->assertSame(
            ['title'],
            $newTables->getTable('products')->getColumnNames()
        );        
    }
    
    public function testGetColumnsMethod()
    {
        $tables = new Tables();
        $tables->add('users a', ['id', 'name']);
        $tables->add('products', ['id', 'title']);
        
        $this->assertSame(
            ['a.id', 'a.name', 'id', 'title'],
            array_keys($tables->getColumns())
        );     
    }    
}