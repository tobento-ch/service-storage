<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\Service\Storage\Grammar;

use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\Query\SubQueryWhere;
use Tobento\Service\Storage\Query\SubQuery;
use Tobento\Service\Storage\Query\JoinClause;
use Tobento\Service\Storage\Tables\TableInterface;
use Tobento\Service\Storage\Tables\ColumnInterface;
use Tobento\Service\Storage\Tables\Column;

/**
 * PdoMySqlGrammar
 */
class PdoMySqlGrammar extends Grammar
{
    /**
     * Get the statement
     *
     * @return null|string 'SELECT id, date_created FROM products' e.g. or null on failure
     */    
    public function getStatement(): null|string
    {
        if ($this->statement !== null) {
            return $this->statement;
        }
        
        if ($this->select !== null)    {
            return $this->statement = $this->getSelectStatement();
        }

        if ($this->insert !== null)    {
            return $this->statement = $this->getInsertStatement();
        }
    
        if ($this->update !== null)    {
            return $this->statement = $this->getUpdateStatement();
        }

        if ($this->delete !== null)    {
            return $this->statement = $this->getDeleteStatement();
        }                    
        
        throw new GrammarException('Invalid Grammar Statement!');
    }

    /**
     * Get the binding
     *
     * @return array
     */    
    public function getBindings(): array
    {
        return !empty($this->bindings) ? $this->bindings : [];
    }

    /**
     * Get the item inserted or updated.
     *
     * @return array
     */    
    public function getItem(): array
    {    
        return $this->item ?: [];
    }
    
    /**
     * Get the select columns.
     *
     * @return array
     */    
    protected function getSelectColumns(): array
    {
        // if specific select columns are set, we verify them.
        if (is_array($this->select) && !empty($this->select))
        {            
            $this->select = $this->queryTables->withColumns($this->select)->getColumns();
        }
        
        // Use the verified columns instead of ['*'].
        $this->select = empty($this->select) ? $this->queryTables->getColumns() : $this->select;
                
        // If index is set, the column must be on the beginning.
        if (
            !is_null($this->index)
            && is_array($this->select)
            && !is_null($indexColumn = $this->queryTables->verifyColumn($this->index))
        ) {
            // add to beginning
            array_unshift($this->select, $indexColumn);
        }

        return is_array($this->select) ? $this->select : [];    
    }    

    /**
     * Get the select statement
     *
     * @return string 'SELECT id, date_created FROM products WHERE ...' e.g.
     */    
    protected function getSelectStatement(): string
    {
        // https://dev.mysql.com/doc/refman/8.0/en/select.html
        // https://dev.mysql.com/doc/refman/8.0/en/join.html
        // A table reference can be aliased using tbl_name AS alias_name or tbl_name alias_name
        
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        $this->queryTables->addTable($table);
        
        // create tables from joins. Call first for verifing table and columns.
        $tables = $this->joinsToTables($this->joins);
        
        // add table as joins to the beginning.
        array_unshift($tables, $this->compileTable($table));
        
        $select = is_string($this->select)
            ? $this->select
            : $this->compileSelectColumns($this->getSelectColumns());
        
        $segments = [
            'SELECT '.$select.' FROM '.implode(' ', $tables),
            $this->compileWheres($this->wheres),
            $this->compileGroups($this->groups),
            $this->compileHavings($this->havings),
            $this->compileOrders($this->orders),
            $this->compileLimit($this->limit)
        ];
        
        $segments = array_filter($segments, fn($v) => !empty($v));
        
        return implode(' ', $segments);
    }
    
    protected function getInsertStatement(): null|string
    {
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        if (is_null($this->insert)) {
            return null;
        }
        
        // joins are (currently) not supported so, we can safely remove table alias.
        $table = $table->withAlias(null);
            
        $this->queryTables->addTable($table);
        
        // update table without alias.
        $this->table($table->name());
        
        $inserts = [];
        
        foreach($this->insert as $name => $value) {
            $inserts[(new Column($name))->name()] = $value;
        }
        
        $queryTables = $this->queryTables->withColumns(array_keys($inserts));
        $columns = $queryTables->getColumnNames();
        
        if (empty($columns)) {
            return null;
        }
        
        // get only those values from the columns verified;
        $values = array_intersect_key($inserts, array_flip($columns));
        
        $this->item = $values;
        
        $this->bindMany(array_values($values));
        
        $table = $this->compileTable($table, false);
        $compiledColumns = $this->compileInsertColumns($queryTables->getColumns());
        
        return 'INSERT INTO '.$table.' ('.$compiledColumns.') VALUES ('.implode(', ', array_fill(0, count($columns), '?')).')';
    }
    
    /**
     * Get the update statement
     *
     * @return null|string 'UPDATE table SET title = :title, name = :name WHERE id = :id' e.g.
     */    
    protected function getUpdateStatement(): null|string
    {
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        if (is_null($this->update)) {
            return null;
        }
        
        // joins are (currently) not supported so, we can safely remove table alias.
        $table = $table->withAlias(null);
        
        $this->queryTables->addTable($table);
        
        // update table without alias.
        $this->table($table->name());
        
        $queryTables = $this->queryTables->withColumns(array_keys($this->update));
        $columns = [];
        
        foreach($queryTables->getColumns() as $column) {
            $columns[] = $column->column();
        }
        
        // primaryKey cannot be set on update
        if ($table->primaryKey()) {
            $columns = array_diff($columns, [$table->primaryKey()]);
        }
        
        if (empty($columns)) {
            return null;
        }             
        
        // get only those values from the columns verified;
        $values = array_intersect_key($this->update, array_flip($columns));
        
        $this->item = $values;
        
        $this->bindMany(array_values($values));
        
        $table = $this->compileTable($table, false);
        
        $columns = $this->compileUpdateColumns($queryTables->getColumns(), $values);
        
        $segments = [
            'UPDATE '.$table.' SET '.$columns,
            $this->compileWheres($this->wheres),
            $this->compileOrders($this->orders),
            $this->compileLimit($this->limit)            
        ];
        
        $segments = array_filter($segments, fn($v) => !empty($v));
        
        return implode(' ', $segments);
    }

    /**
     * Compile insert columns.
     *
     * @param array<string, ColumnInterface> $columns The columns.
     * @return string The compiled insert columns.
     */    
    protected function compileInsertColumns(array $columns): string
    {
        $compiled = [];
        
        foreach($columns as $column)
        {
            $compiled[] = $this->backtickValue($column->name());
        }
        
        $compiled = array_unique($compiled);
        
        return implode(',', $compiled);
    }
    
    /**
     * Compile update columns.
     *
     * @param array<string, ColumnInterface> $columns The columns.
     * @param array $values
     * @return string The compiled update columns.
     */    
    protected function compileUpdateColumns(array $columns, array $values): string
    {
        $compiled = [];
        
        foreach($columns as $column)
        {
            if ($column->jsonSegments()) {
                [$col, $path] = $this->buildJsonColumnAndPath($column);
                
                if (isset($values[$column->column()]) && is_array($values[$column->column()])) {
                    $compiled[] = $this->backtickValue($column->name()).' = json_set('.$col.', \'$.'.$path.'\', cast(? as json))';
                } else {
                    $compiled[] = $this->backtickValue($column->name()).' = json_set('.$col.', \'$.'.$path.'\', ?)';
                }
            } else {
                $compiled[] = $this->backtickValue($column->name()).' = ?';
            }
        }
        
        return implode(', ', $compiled);
    }

    /**
     * Get the delete statement
     *
     * @return null|string 'DELETE FROM table WHERE id = :id' e.g.
     */    
    protected function getDeleteStatement(): null|string
    {
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        // joins are (currently) not supported so, we can safely remove table alias.
        $table = $table->withAlias(null);
            
        $this->queryTables->addTable($table);
        
        // update table without alias.
        $this->table($table->name());
        
        $segments = [
            'DELETE FROM '.$this->compileTable($table, false),
            $this->compileWheres($this->wheres),
            $this->compileOrders($this->orders),
            $this->compileLimit($this->limit)            
        ];
        
        $segments = array_filter($segments, fn($v) => !empty($v));
        
        return implode(' ', $segments);
    }

    /**
     * Joins queries to tables.
     *
     * @param array $joins [JoinClause, ...]
     * @return array ['LEFT JOIN product_category t on a.id = t.product_id', ...]
     */    
    protected function joinsToTables(array $joins): array
    {
        $tables = [];
        
        foreach($joins as $join)
        {
            $clause = $this->compileJoinClause($join);

            if ($clause !== null) {
                $tables[] = $clause;    
            }
        }
        
        return $tables;
    }

    /**
     * Compile a join clause.
     *
     * @param JoinClause $join
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function compileJoinClause(JoinClause $join): null|string
    {
        if (!is_null($join->callback())) {
            call_user_func($join->callback(), $join);
        }
        
        if (is_null($table = $this->tables->verifyTable($join->table()))) {
            return null;
        }
        
        $this->queryTables->addTable($table);
                
        $wheres = $this->compileWheres($join->storage()->getWheres(), '');
        
        if (empty($wheres)) {
            $this->queryTables->removeTable($join->table());
            return null;
        }

        $direction = 'INNER JOIN';

        switch ($join->direction())
        {
            case 'left':
                $direction = 'LEFT JOIN';
                break;
            case 'right':
                $direction = 'RIGHT JOIN';
                break;
        }
                
        return $direction. ' '.$this->compileTable($table).' on '.$wheres;
    }
    
    /**
     * Compile wheres clauses.
     *
     * @param array $wheres [[type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and'], ...]
     * @param string $prefix The prefix such as 'WHERE'.
     * @return string
     */    
    protected function compileWheres(array $wheres, string $prefix = 'WHERE '): string
    {
        if (empty($wheres)) {
            return '';
        }
        
        $clauses = [];
        
        foreach($wheres as $where)
        {
            if (!method_exists($this, 'where'.$where['type'])) {
                continue;
            }
            
            $clause = $this->{"where{$where['type']}"}($where);
            
            if ($clause !== null) {
                $clauses[] = $clause;
            }
        }

        if (empty($clauses)) {
            return '';
        }
        
        // remove leading and or
        return $prefix.preg_replace('/and |or /i', '', implode(' ', $clauses), 1);
    }

    /**
     * Compile where base clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereBase(array $where): null|string
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->compileWhereNested($where['column'], $where);
        }
        
        if ($where['value'] instanceof SubQueryWhere) {
            return $this->compileWhereSubquery($where['value'], $where);
        }    
        
        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }
        
        if (is_null($operator = $this->verifyOperator($where['operator']))) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    

        $value = $this->verifyValue($where['value']);
        
        if (is_null($value) && $where['value'] !== null) {
            return null;
        }
        
        $this->bind($where['value']);
        
        $column = $this->compileWhereColumn($column);

        return $where['boolean'].' '.$column.' '.$operator.' ?';
    }

    /**
     * Compile where column clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereColumn(array $where): null|string
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->compileWhereNested($where['column'], $where);
        }
        
        if ($where['value'] instanceof SubQueryWhere) {
            return $this->compileWhereSubquery($where['value'], $where);
        }     
        
        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }
    
        if (is_null($operator = $this->verifyOperator($where['operator']))) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    

        if (is_null($value = $this->queryTables->verifyColumn($where['value']))) {
            return null;
        }
        
        $column = $this->compileWhereColumn($column);
        $value = $this->compileWhereColumn($value);

        return $where['boolean'].' '.$column.' '.$operator.' '.$value;
    }
    
    /**
     * Compile where is null clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereNull(array $where): null|string
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->compileWhereNested($where['column'], $where);
        }

        if (!is_string($where['column'])) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }
        
        $col = $this->compileWhereColumn($column);
    
        $clause = $where['boolean'].' '.$col.' is null';
        
        if ($column->jsonSegments()) {
            $clause = '('.$clause.' or '.$col.' = \'NULL\')';
        }

        return $clause;
    }

    /**
     * Compile where is not null clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereNotNull(array $where): null|string
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->compileWhereNested($where['column'], $where);
        }

        if (!is_string($where['column'])) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }
        
        $col = $this->compileWhereColumn($column);
        
        $clause = $where['boolean'].' '.$col.' is not null';
        
        if ($column->jsonSegments()) {
            $clause = '('.$clause.' AND '.$col.' != \'NULL\')';
        }
        
        return $clause;
    }
    
    /**
     * Compile where in clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereIn(array $where): null|string
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->compileWhereNested($where['column'], $where);
        }

        if ($where['value'] instanceof SubQueryWhere) {
            return $this->compileWhereSubquery($where['value'], $where);
        }    

        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }

        if (!in_array($where['operator'], ['IN', 'NOT IN'])) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }
        
        if (!is_array($where['value']) || empty($where['value'])) {
            return null;
        }

        $values = array_values($where['value']);
        
        $this->bindMany($values);
        
        $column = $this->compileWhereColumn($column);
        
        return $where['boolean'].' '.$column.' '.$where['operator'].' ('.implode(', ', array_fill(0, count($values), '?')).')';
    }

    /**
     * Compile where between clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereBetween(array $where): null|string
    {
        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }

        if (!is_array($where['value']) || empty($where['value'])) {
            return null;
        }
        
        $between = $where['operator'] === '!=' ? 'not between' : 'between';

        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }
        
        $values = array_values($where['value']);
        
        $this->bind($values[0] ?? null);
        $this->bind($values[1] ?? null);
        
        $column = $this->compileWhereColumn($column);
        
        return $where['boolean'].' '.$column.' '.$between.' ? and ?';
    }
    
    /**
     * Compile where not between clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereNotBetween(array $where): null|string
    {
        return $this->whereBetween($where);
    }
    
    /**
     * Compile where Json contains clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */
    protected function whereJsonContains(array $where): null|string
    {
        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }

        if (!in_array($where['operator'], ['=', '!='])) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }
        
        $value = $this->encodeJsonValue($where['value']);
        
        if (is_null($value)) {
            return null;
        }

        $this->bind($value);

        [$column, $path] = $this->buildJsonColumnAndPath($column);
        
        if (is_null($path)) {
            return $where['boolean'].' json_contains('.$column.', ?)';
        }
        
        return $where['boolean'].' json_contains('.$column.', ?, \'$.'.$path.'\')';
    }
    
    /**
     * Compile where Json contains key clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */
    protected function whereJsonContainsKey(array $where): null|string
    {
        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }

        if (!in_array($where['operator'], ['=', '!='])) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }
        
        if (empty($column->jsonSegments())) {
            return null;
        }

        [$column, $path] = $this->buildJsonColumnAndPath($column);
        
        return $where['boolean'].' ifnull(json_contains_path('.$column.', \'one\', \'$.'.$path.'\'), 0)';
    }
    
    /**
     * Compile where Json length clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */
    protected function whereJsonLength(array $where): null|string
    {
        if (!is_string($where['column'])) {
            return null;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }

        if (is_null($operator = $this->verifyOperator($where['operator']))) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    
        
        $value = $this->verifyValue($where['value']);
        
        if (is_null($value) && $where['value'] !== null) {
            return null;
        }

        $this->bind($value);

        [$column, $path] = $this->buildJsonColumnAndPath($column);
        
        if (is_null($path)) {
            return $where['boolean'].' json_length('.$column.') '.$operator.' ?';
        }
        
        return $where['boolean'].' json_length('.$column.', \'$.'.$path.'\') '.$operator.' ?';
    }    

    /**
     * Returns the build json column and path.
     *
     * @param ColumnInterface $column
     * @return array [null, null] on failure, otherwise the ['column', 'path'].
     */    
    protected function buildJsonColumnAndPath(ColumnInterface $column): array
    {
        if (empty($column->jsonSegments())) {
            return [$this->compileWhereColumn($column), null];
        }
        
        // handle json path
        $path = '';

        foreach($column->jsonSegments() as $segment)
        {
            $path .= '"'.$segment.'".';
        }

        $path = rtrim($path, '.');
        
        return [
            $this->compileWhereColumn($column->withJsonSegments(null)),
            $path
        ];        
    }
    
    /**
     * Compile where Raw clause.
     *
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function whereRaw(array $where): null|string
    {
        $values = array_values($where['value']);
        
        $this->bindMany($values);
        
        return $where['column'];
    }
    
    /**
     * Compile a nested where clause.
     *
     * @param SubQueryWhere $query
     * @param array $where The where parameters
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function compileWhereNested(SubQueryWhere $query, array $where): null|string
    {
        call_user_func($query->callback(), $query->storage());
        
        if (!empty($query->storage()->getTable())) {
            return $this->compileWhereSubqueryValued($query, $where);
        }
        
        $wheres = $this->compileWheres($query->storage()->getWheres(), '');
        
        if (empty($wheres)) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    
        
        return $where['boolean'].' ('.$wheres.')';
    }

    /**
     * Compile a where subquery clause with a value.
     *
     * @param SubQueryWhere $query
     * @param array $where The where parameters
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function compileWhereSubqueryValued(SubQueryWhere $query, array $where): null|string
    {
        $value = $this->verifyValue($where['value']);
        
        if (is_null($value) && $where['value'] !== null){
            return null;
        }

        if ($this->verifyOperator($where['operator']) === null) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }    
        
        $grammar = $this->createGrammarFromStorage($query->storage());
        
        if (is_null($statement = $grammar->getStatement())) {
            return null;
        }

        $this->bind($where['value']);
        $this->bindMany($grammar->getBindings());
        
        return $where['boolean'].' ('.$statement.') '.$where['operator'].' ?';
    }
    
    /**
     * Compile a where subquery clause.
     *
     * @param SubQueryWhere $query
     * @param array $where The where parameters
     * @return null|string Null on failure, otherwise the clause.
     */    
    protected function compileWhereSubquery(SubQueryWhere $query, array $where): null|string
    {
        $callback = call_user_func($query->callback(), $query->storage());

        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return null;
        }

        if (
            $this->verifyOperator($where['operator']) === null
            && !in_array($where['operator'], ['IN', 'NOT IN', 'ANY', 'SOME'])
        ) {
            return null;
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return null;
        }
        
        $column = $this->compileWhereColumn($column);
        
        if ($callback instanceof SubQuery) {
            
            if (empty($callback->statement())) {
                return null;
            }
            
            $this->bindMany($callback->bindings());

            return $where['boolean'].' '.$column.' '.$where['operator'].' ('.$callback->statement().')';            
        }
        
        if (empty($query->storage()->getTable())) {
            return null;
        }
        
        $grammar = $this->createGrammarFromStorage($query->storage());
        
        if (is_null($statement = $grammar->getStatement())) {
            return null;
        }

        $this->bindMany($grammar->getBindings());

        return $where['boolean'].' '.$column.' '.$where['operator'].' ('.$statement.')';
    }

    /**
     * Compile groups clauses.
     *
     * @param array $groups [['column' => $column, 'type' => 'Base|Raw'], ...]
     * @return string
     */    
    protected function compileGroups(array $groups): string
    {
        if (empty($groups))    {
            return '';
        }
        
        $clauses = [];
        
        foreach($groups as $group)
        {            
            if (!is_string($group['column'])) {
                continue;
            }
            
            if ($group['type'] === 'RAW') {
                $clauses[] = $group['column'];
                continue;
            }
            
            if (is_null($column = $this->queryTables->verifyColumn($group['column']))){
                continue;
            }
            
            $column = $this->compileWhereColumn($column);

            $clauses[] = $column;
        }

        if (empty($clauses)) {
            return '';
        }
        
        return 'GROUP BY '.implode(', ', $clauses);
    }

    /**
     * Compile havings clauses.
     *
     * @param array $havings [['column' => $column, 'type' => 'Base|Between', ...], ...]
     * @return string
     */    
    protected function compileHavings(array $havings): string
    {
        if (empty($havings)) {
            return '';
        }
        
        $clauses = [];
        
        foreach($havings as $having)
        {
            $clause = null;

            if ($having['type'] === 'Base') {
                $clause = $this->compileHavingBase($having);
            } elseif ($having['type'] === 'Between') {
                $clause = $this->compileHavingBetween($having);
            }
                        
            if ($clause !== null) {
                $clauses[] = $clause;
            }
        }

        if (empty($clauses)) {
            return '';
        }
        
        // remove leading and or
        return 'HAVING '.preg_replace('/and |or /i', '', implode(' ', $clauses), 1);
    }

    /**
     * Compile having Base clause.
     *
     * @param array $having ['column' => $column, 'type' => 'Base', ...]
     * @return null|string
     */    
    protected function compileHavingBase(array $having): null|string
    {
        if (!is_string($having['column'])) {
            return null;
        }
        
        if (is_null($column = $this->queryTables->verifyColumn($having['column']))){
            return null;
        }
        
        if (is_null($this->verifyOperator($having['operator']))) {
            return null;
        }
        
        if (!in_array($having['boolean'], ['and', 'or'])) {
            return null;
        }
        
        $value = $this->verifyValue($having['value']);
        
        if (is_null($value) && $having['value'] !== null){
            return null;
        }

        $this->bind($having['value']);
        
        $column = $this->compileWhereColumn($column);
            
        return $having['boolean'].' '.$column.' '.$having['operator'].' ?';
    }
    
    /**
     * Compile having Between clause.
     *
     * @param array $having ['column' => $column, 'type' => 'Between', ...]
     * @return null|string
     */    
    protected function compileHavingBetween(array $having): null|string
    {
        $between = $having['not'] ? 'not between' : 'between';

        if (!is_string($having['column'])) {
            return null;
        }
        
        if (is_null($column = $this->queryTables->verifyColumn($having['column']))){
            return null;
        }        
 
        if (!in_array($having['boolean'], ['and', 'or'])) {
            return null;
        }
        
        $min = $having['value'][0] ?? null;
        $max = $having['value'][1] ?? null;

        if (is_null($min) || is_null($max)){
            return null;
        }
        
        $this->bind($min);
        $this->bind($max);
        
        $column = $this->compileWhereColumn($column);
        
        return $having['boolean'].' '.$column.' '.$between.' ? and ?';
    }
        
    /**
     * Compile orders clause.
     *
     * @param array $orders ['column' => 'name', 'direction' => 'asc']
     * @return string
     */    
    protected function compileOrders(array $orders): string
    {
        if (empty($orders)) {
            return '';
        }
        
        $verifiedOrders = '';
        
        foreach($orders as $order)
        {
            if (is_null($column = $this->queryTables->verifyColumn($order['column']))) {
                continue;
            }
                        
            $column = $this->compileWhereColumn($column);
            
            $boolean = empty($verifiedOrders) ? '' : ', ';
            $direction = strtolower($order['direction']) === 'asc' ? 'ASC' : 'DESC';
            $verifiedOrders .= $boolean.$column.' '.$direction;
        }
        
        if (empty($verifiedOrders)) {
            return '';
        }
        
        return 'ORDER BY '.$verifiedOrders;
    }
        
    /**
     * Compile limit clause.
     *
     * @param null|array $limit [12, 0], [number, offset]
     * @return string
     */    
    protected function compileLimit(?array $limit): string
    {
        if (empty($limit)) {
            return '';
        }
        
        $offset = $limit[1] ?? 0;

        return 'Limit '.(int) $offset.', '.(int) $limit[0];
    }
    
    /**
     * Binds a parameter.
     *
     * @param mixed $value The value.
     * @return void
     */    
    protected function bind(mixed $value): void
    {
        $this->bindings[] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Binds many parameters.
     *
     * @param array $values The values.
     * @return void
     */    
    protected function bindMany(array $values): void
    {
        foreach($values as $value) {
            $this->bind($value);
        }
    }

    /**
     * Compile table.
     *
     * @param TableInterface $table
     * @param bool $withAliasIfAny
     * @return string
     */    
    protected function compileTable(TableInterface $table, bool $withAliasIfAny = true): string
    {                
        $compiled = $this->backtickValue($table->name());
        
        if ($withAliasIfAny && $table->alias()) {
            $compiled .= ' as '.$this->backtickValue($table->alias());
        }
        
        return $compiled;
    }
    
    /**
     * Compile select columns.
     *
     * @param array<string, ColumnInterface> $columns The columns.
     * @return string The compiled select columns.
     */    
    protected function compileSelectColumns(array $columns): string
    {
        $compiled = [];
        
        foreach($columns as $column)
        {            
            $compiled[] = $this->compileSelectColumn($column);
        }
        
        return implode(',', $compiled);
    }
    
    /**
     * Compile select column.
     *
     * @param ColumnInterface $column
     * @return string The compiled select column.
     */    
    protected function compileSelectColumn(ColumnInterface $column): string
    {
        $compiled = $this->compileWhereColumn($column);
        
        if ($column->alias()) {
            $compiled .= ' as '.$this->backtickValue($column->alias());
        }
            
        return $compiled;
    }
    
    /**
     * Compile where column.
     *
     * @param ColumnInterface $column
     * @return string
     */    
    protected function compileWhereColumn(ColumnInterface $column): string
    {
        $compiled = '';
        
        if ($column->tableAlias()) {
            $compiled .= $this->backtickValue($column->tableAlias()).'.';
        }
        
        $compiled .= $this->backtickValue($column->name());
        
        // handle json
        if ($column->jsonSegments()) {
            $path = '';
            
            foreach($column->jsonSegments() as $segment)
            {
                $path .= '"'.$segment.'".';
            }
            
            $path = rtrim($path, '.');
            
            return 'json_unquote(json_extract('.$compiled.', \'$.'.$path.'\'))';
        }
            
        return $compiled;
    }

    /**
     * Backtick value.
     *
     * @param string $value
     * @return string
     */    
    protected function backtickValue(string $value): string
    {
        return '`'.$value.'`';
    }

    /**
     * Create a new grammar from the storage.
     *
     * @param StorageInterface $storage
     * @return static A new instance
     */    
    protected function createGrammarFromStorage(StorageInterface $storage): static
    {        
        return (new static($this->tables))
            ->select($storage->getSelect())
            ->table($storage->getTable())
            ->joins($storage->getJoins())
            ->wheres($storage->getWheres())
            ->groups($storage->getGroups())
            ->havings($storage->getHavings())
            ->orders($storage->getOrders())
            ->limit($storage->getLimit())
            ->bindings($storage->getBindings());
    }
}