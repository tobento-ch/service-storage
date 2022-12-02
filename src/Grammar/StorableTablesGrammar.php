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
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\Tables\ColumnInterface;
use Tobento\Service\Storage\Tables\Column;
use Tobento\Service\Storage\Query\SubQueryWhere;
use Tobento\Service\Storage\Query\SubQuery;
use Tobento\Service\Storage\Query\JoinClause;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Iterable\Iter;
use JsonException;
use Throwable;

/**
 * StorableTablesGrammar
 */
class StorableTablesGrammar extends Grammar
{
    /**
     * @var null|array The item(s) fetched.
     */    
    protected null|array $items = null;
    
    /**
     * Create a new StorableTablesGrammar
     *
     * @param StorageInterface $storage
     * @param TablesInterface $tables
     */    
    public function __construct(
        protected StorageInterface $storage,
        TablesInterface $tables,
    ) {
        parent::__construct($tables);
    }
    
    /**
     * Get the item(s) based on the statement.
     *
     * @return null|array The item(s) or null
     */    
    public function execute(): null|array
    {        
        if ($this->items !== null) {
            return $this->items;
        }
        
        if ($this->select !== null) {
            return $this->items = $this->executeSelectStatement();
        }

        if ($this->insert !== null) {
            return $this->items = $this->executeInsertStatement();
        }
        
        if ($this->insertItems !== null) {
            return $this->items = $this->executeInsertItemsStatement();
        }        
    
        if ($this->update !== null) {
            return $this->items = $this->executeUpdateStatement();
        }

        if ($this->delete !== null) {
            return $this->items = $this->executeDeleteStatement();
        }
        
        throw new GrammarException('Invalid Grammar Execution!');
    }
    
    /**
     * Execute the select statement
     *
     * @return array The items
     */    
    protected function executeSelectStatement(): array
    {
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        $this->queryTables->addTable($table);
        
        $items = $this->storage->fetchItems($table->name());
        $items = Iter::toArray(iterable: $items);
        
        // add table alias if any
        $items = $this->addTableAliasToItemKeys($items, $table->alias());
        
        // apply join
        $items = $this->applyJoins($items, $this->joins);

        // apply where
        $items = $this->applyWheres($items, $this->wheres);
        
        // apply group by and havings
        $items = $this->applyGroups($items, $this->groups, $this->havings);
        
        // apply orders
        $items = $this->applyOrders($items, $this->orders);
        
        // apply limit
        $items = $this->applyLimit($items, $this->limit);
  
        // apply index if is set
        if (!is_null($this->index)) {
            $items = $this->applyIndexColumn($items, $this->index);
        }
                
        // apply select columns
        $items = $this->applySelectColumns($items);
        
        // remove table alias if any
        $items = $this->removeTableAliasToItemKeys($items, $table->alias());
        
        return $items;
    }
    
    /**
     * Execute the insert statement
     *
     * @return null|array The item inserted or null.
     */    
    protected function executeInsertStatement(): null|array
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
        
        $columns = $this->queryTables->withColumns(array_keys($inserts))->getColumnNames();
        
        if (empty($columns)) {
            return null;
        }
        
        $items = $this->storage->fetchItems($table->name());
        $items = Iter::toArray(iterable: $items);
        
        // get only those values from the columns verified.
        $item = array_intersect_key($inserts, array_flip($columns));
        
        if ($table->primaryKey()) {
            $item[$table->primaryKey()] ??= $this->autoIncrement($table->primaryKey(), $items);
        }
        
        $this->item = $item;
        
        if (is_array($this->return)) {
            
            if (empty($this->return)) {
                $this->return = $this->queryTables->getColumnNames();
            }
            
            $this->item = array_intersect_key($this->item, array_flip($this->return));
        }
        
        if (is_null($this->return)) {
            $this->item = [];
        }
        
        foreach($item as $key => $value) {
            $item[$key] = is_array($value) ? json_encode($value) : $value;
        }
        
        $items[] = $item;
                
        $this->storage->storeItems($table->name(), $items);
        
        return $item;
    }
    
    /**
     * Execute the insert statement
     *
     * @return null|array The item inserted or null.
     */    
    protected function executeInsertItemsStatement(): null|array
    {
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        if (is_null($this->insertItems)) {
            return null;
        }        
        
        // joins are (currently) not supported so, we can safely remove table alias.
        $table = $table->withAlias(null);
            
        $this->queryTables->addTable($table);
        
        // update table without alias.
        $this->table($table->name());
        
        $insertItems = Iter::toArray(iterable: $this->insertItems);
        
        $firstItem = $insertItems[array_key_first($insertItems)] ?? [];
        $firstItemColumns = [];
        
        foreach(array_keys($firstItem) as $name) {
            $firstItemColumns[] = (new Column($name))->name();
        }
        
        $queryTables = $this->queryTables->withColumns($firstItemColumns);
        $columns = $queryTables->getColumnNames();
        
        if (empty($columns)) {
            return null;
        }
        
        $items = $this->storage->fetchItems($table->name());
        $items = Iter::toArray(iterable: $items);
        $itemsCreated = [];
        $flipColumns =  array_flip($columns);
        
        if ($table->primaryKey()) {
            $id = $this->autoIncrement($table->primaryKey(), $items);
        }
        
        foreach($insertItems as $item) {
            // get only those values from the columns verified;
            $item = array_intersect_key($item, $flipColumns);
            
            if ($table->primaryKey()) {
                $item[$table->primaryKey()] ??= $id;
                $id++;
            }
            
            foreach($item as $key => $value) {
                $item[$key] = is_array($value) ? json_encode($value) : $value;
            }
            
            $items[] = $item;
            
            if (is_array($this->return) && !empty($this->return)) {
                $item = array_intersect_key($item, array_flip($this->return));
            }
            
            $itemsCreated[] = $item;
        }
        
        $this->storage->storeItems($table->name(), $items);
        
        return $itemsCreated;        
    }    

    /**
     * Auto increment.
     *
     * @param string $column The column for auto incementing.
     * @param array $items
     * @return int
     */    
    protected function autoIncrement(string $column, array $items): int
    {
        $values = array_column($items, $column);
        
        if (empty($values)) {
            return 1;
        }
        
        $max = max($values);
        
        $max = (int) $max + 1;
        
        // do we want to check for dublicate id and throw an exception?
        
        return $max;
    }
    
    /**
     * Execute the update statement
     *
     * @return null|array The item(s) updated or null.
     */    
    protected function executeUpdateStatement(): null|array
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
        
        $columns = $queryTables->getColumnNames();
        $jsonColumns = [];
                
        // primaryKey cannot be set on update
        if ($table->primaryKey()) {
            $columns = array_diff($columns, [$table->primaryKey()]);
        }
        
        if (empty($columns)) {
            return null;
        }        
        
        // get only those values from the columns verified
        $item = array_intersect_key($this->update, array_flip($columns));
        $this->item = $item;
        
        foreach($queryTables->getColumns() as $column) {
            if ($column->jsonSegments()) {
                $jsonColumns[] = $column;
                $this->item[$column->column()] = $this->update[$column->column()] ?? null;
            }
        }
        
        foreach($item as $key => $value) {
            $item[$key] = is_array($value) ? json_encode($value) : $value;
        }        
        
        // update all items found with the item.
        $this->index = null; // do not allow index for update.
        
        $foundItems = $this->executeSelectStatement();
        $itemsUpdated = [];
        
        foreach($foundItems as $index => $foundItem)
        {
            $updatedItem = array_merge($foundItem, $item);
            
            $updatedItem = $this->updateItemWithJsonColumns($updatedItem, $jsonColumns, $this->update);
            
            $foundItems[$index] = $updatedItem;
                        
            if (is_array($this->return) && !empty($this->return)) {
                $updatedItem = array_intersect_key($updatedItem, array_flip($this->return));
            }
            
            $itemsUpdated[$index] = $updatedItem;
        }
        
        $items = $this->storage->fetchItems($table->name());
        $items = Iter::toArray(iterable: $items);

        $items = array_replace($items, $foundItems);
        
        $this->storage->storeItems($table->name(), $items);
        
        return $itemsUpdated;
    }

    /**
     * Update json columns
     *
     * @param array $item The item to update.
     * @param array $columns
     * @param array $values
     * @return array The item updated
     */    
    protected function updateItemWithJsonColumns(array $item, array $columns, array $values): array
    {
        foreach($columns as $column)
        {
            $path = implode('.', $column->jsonSegments());
            $colNrm = $column->withJsonSegments(null);
            
            if (!array_key_exists($colNrm->column(), $item)) {
                $item[$colNrm->column()] = $values[$column->column()];
                continue;
            }
            
            try {
                $columnValue = json_decode($item[$colNrm->column()], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException|Throwable $e) {
                $columnValue = $item[$colNrm->column()];
            }
            
            if (is_array($columnValue)) {
                $columnValue = Arr::set($columnValue, $path, $values[$column->column()]);
            } else {
                continue;
            }
            
            try {
                $item[$colNrm->column()] = json_encode($columnValue, JSON_THROW_ON_ERROR);
            } catch (JsonException|Throwable $e) {
                //
            }
        }
        
        return $item;
    }
    
    /**
     * Exceute the delete statement
     *
     * @return null|array The item(s) deleted or null.
     */    
    protected function executeDeleteStatement(): null|array
    {
        if (is_null($table = $this->tables->verifyTable($this->table))) {
            throw new GrammarException('Invalid Table ['.(string)$this->table.']!');
        }
        
        // joins are (currently) not supported so, we can safely remove table alias.
        $table = $table->withAlias(null);
            
        $this->queryTables->addTable($table);
        
        // update table without alias.
        $this->table($table->name());
        
        // delete all items found and update storage data.
        $this->index = null; // do not allow index for delete.
        
        $foundItems = $this->executeSelectStatement();
        
        $items = $this->storage->fetchItems($table->name());
        $items = Iter::toArray(iterable: $items);
        
        $items = array_diff_key($items, $foundItems);                
        
        $this->storage->storeItems($table->name(), $items);
        
        if (is_null($this->return)) {
            return [];
        }
        
        if (!empty($this->return)) {
            
            $return = array_flip($this->return);
                
            foreach($foundItems as $key => $item) {
                $foundItems[$key] = array_intersect_key($item, $return);
            }
        }
        
        return $foundItems;
    }

    /**
     * Add table alias to item keys.
     *
     * @param array $items The items
     * @param null|string $alias The table alias
     * @return array The items
     */    
    protected function addTableAliasToItemKeys(array $items, null|string $alias): array
    {
        if (is_null($alias)) {
            return $items;
        }
        
        $aliased = [];
        
        foreach($items as $index => $item)
        {
            foreach($item as $key => $value)
            {
                $aliased[$index][$alias.'.'.$key] = $value;
            }
        }
        
        return $aliased;      
    }

    /**
     * Remove table alias from item keys.
     *
     * @param array $items The items
     * @param null|string $alias The table alias
     * @return array The items
     */    
    protected function removeTableAliasToItemKeys(array $items, null|string $alias): array
    {
        if (is_null($alias)) {
            return $items;
        }
        
        $aliased = [];
        
        foreach($items as $index => $item)
        {
            foreach($item as $key => $value)
            {
                $key = preg_replace('/^[a-zAZ]+\./', '', $key, 1);
                
                $aliased[$index][$key] = $value;
            }
        }
        
        return $aliased;        
    }
    
    /**
     * Get the select columns.
     *
     * @return array<string, ColumnInterface>
     */    
    protected function getSelectColumns(): array
    {        
        if (is_array($this->select) && !empty($this->select))
        {
            $this->select = $this->queryTables->withColumns($this->select)->getColumns();
        }
                
        // Use the verified columns instead of ['*'].
        $this->select = empty($this->select) ? $this->queryTables->getColumns() : $this->select;
        
        return $this->select;    
    }
    
    /**
     * Apply the select columns.
     *
     * @param array $items The items
     * @return array The items
     */    
    protected function applySelectColumns(array $items): array
    {        
        $selectedColumns = $this->getSelectColumns();        
        $filtered = [];
        $normalizedColumns = [];
        $aliases = [];
        
        foreach($selectedColumns as $column)
        {            
            $newColumn = $column->withAlias(null)->withJsonSegments(null);

            if (!is_null($column->alias())) {
                $aliases[] = [$newColumn->column(), $column->alias(), $column->jsonSegments()];
            }
            
            $normalizedColumns[$newColumn->column()] = null;
        }
        
        foreach($items as $key => $item)
        {            
            $filtered[$key] = array_intersect_key($item, $normalizedColumns);       
            
            // replace with column alias
            if (!empty($aliases))
            {
                foreach($aliases as [$column, $alias, $jsonSegments])
                {
                    if (array_key_exists($column, $filtered[$key])){
                        
                        $value = $filtered[$key][$column];
                        
                        if ($jsonSegments) {
                            try {
                                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                                $value = Arr::get($value, implode('.', $jsonSegments));
                            } catch (JsonException|Throwable $e) {
                                //
                            }
                        }
                        
                        $filtered[$key][$alias] = $value;
                    }
                }
                
                unset($filtered[$key][$column]);
            }    
        }
        
        return $filtered;
    }    

    /**
     * Apply the index column.
     *
     * @param array $items The items
     * @param string $indexColumn The index column
     * @return array The items
     */    
    protected function applyIndexColumn(array $items, string $indexColumn): array
    {
        if (is_null($column = $this->queryTables->verifyColumn($indexColumn)))
        {
            return $items;
        }
  
        $indexed = [];

        foreach($items as $item)
        {
            if (array_key_exists($column->column(), $item)) {
                $indexed[$item[$column->column()]] = $item;
            } else {
                $indexed[] = $item; 
            }  
        }
        
        return $indexed;
    }
    
    /**
     * Apply joins clauses to items.
     *
     * @param array $items The items
     * @param array $joins [JoinClause, ...]
     * @return array The items
     */    
    protected function applyJoins(array $items, array $joins): array
    {        
        foreach($joins as $join)
        {
            $items = $this->applyJoinClause($items, $join);
        }
        
        return $items;
    }

    /**
     * Apply a join clause.
     *
     * @param array $items The items
     * @param JoinClause $join
     * @return array The items
     */    
    protected function applyJoinClause(array $items, JoinClause $join): array
    {
        if ($join->callback() !== null)    {
            call_user_func($join->callback(), $join);
        }

        if (is_null($table = $this->tables->verifyTable($join->table()))) {
            return $items;
        }
        
        $this->queryTables->addTable($table);

        $joinItems = $this->storage->fetchItems($table->name());
        $joinItems = Iter::toArray(iterable: $joinItems);
        
        if (empty($joinItems)) {
            $this->queryTables->removeTable($join->table());
            return $items;
        }    
        
        // add table alias if any
        $joinItems = $this->addTableAliasToItemKeys($joinItems, $table->alias());
        
        $wheres = [];
        $columnWheres = [];
        
        foreach($join->storage()->getWheres() as $where) {
            if ($where['type'] === 'Column') {
                $columnWheres[] = $where;
            } else {
                $wheres[] = $where;
            }
        }
        
        // apply wheres
        $joinItems = $this->applyWheres($joinItems, $wheres);

        if (empty($joinItems)) {
            $this->queryTables->removeTable($join->table());
            return $items;
        }

        // handle join direction.
        switch ($join->direction()) {
            case 'inner':
                $items = $this->applyInnerJoin($items, $joinItems, $join, $columnWheres);
                break;
            case 'left':
                $items = $this->applyLeftJoin($items, $joinItems, $join, $columnWheres);
                break;
            case 'right':
                $items = $this->applyRightJoin($items, $joinItems, $join, $columnWheres);
                break;
        }
        
        return $items;
    }
    
    /**
     * Apply inner join.
     *
     * @param array $items The items
     * @param array $joinItems The join items
     * @param JoinClause $join
     * @param array $wheres The where column clauses
     * @return array The items
     */    
    protected function applyInnerJoin(array $items, array $joinItems, JoinClause $join, array $wheres): array
    {
        $mergedItems = [];

        foreach($items as $item)
        {
            foreach($joinItems as $joinItem)
            {
                $mergedItems[] = array_merge($item, $joinItem);
            }
        }
        
        // apply wheres and reindex array using array values.
        return array_values($this->applyWheres($mergedItems, $wheres));
    }

    /**
     * Apply left join.
     *
     * @param array $items The items
     * @param array $joinItems The join items
     * @param JoinClause $join
     * @param array $wheres The where column clauses
     * @return array The items
     */    
    protected function applyLeftJoin(array $items, array $joinItems, JoinClause $join, array $wheres): array
    {        
        $mergedItems = $this->applyInnerJoin($items, $joinItems, $join, $wheres);
        
        // get first item and fill keys.
        $firstJoinItem = $joinItems[array_key_first($joinItems)] ?? [];
        $firstJoinItem = array_fill_keys(array_keys($firstJoinItem), null);
        $ids = [];
        
        // collect all where column values.
        foreach($mergedItems as $mergedItem)
        {
            foreach($wheres as $where)
            {
                $ids[$where['column']][] = $mergedItem[$where['column']] ?? null;
            }
        }
        
        // next, we merge missing items.
        foreach($items as $item)
        {
            foreach($wheres as $where)
            {
                if (!isset($item[$where['column']]))
                {
                    continue;    
                }
                
                if (!in_array($item[$where['column']], $ids[$where['column']]))
                {
                    // store as to merge once only.
                    $ids[$where['column']][] = $item[$where['column']];
                    
                    // merge items.
                    $mergedItems[] = array_merge($item, $firstJoinItem);
                    continue;
                }
            }
        }
        
        return array_values($mergedItems);
    }
    
    /**
     * Apply right join.
     *
     * @param array $items The items
     * @param array $joinItems The join items
     * @param JoinClause $join
     * @param array $wheres The where column clauses
     * @return array The items
     */    
    protected function applyRightJoin(array $items, array $joinItems, JoinClause $join, array $wheres): array
    {
        $mergedItems = $this->applyInnerJoin($items, $joinItems, $join, $wheres);
        
        // get first item and fill keys.
        $firstJoinItem = $joinItems[array_key_first($joinItems)] ?? [];
        $firstJoinItem = array_fill_keys(array_keys($firstJoinItem), null);
        $ids = [];
        
        // collect all where column values.
        foreach($mergedItems as $mergedItem)
        {
            foreach($wheres as $where)
            {
                $ids[$where['column']][] = $mergedItem[$where['column']] ?? null;
            }
        }
        
        // next, we merge missing items.
        foreach($joinItems as $item)
        {
            foreach($wheres as $where)
            {
                if (!isset($item[$where['column']]))
                {
                    continue;    
                }
                
                if (!in_array($item[$where['column']], $ids[$where['column']]))
                {
                    // store as to merge once only.
                    $ids[$where['column']][] = $item[$where['column']];
                    
                    // merge items.
                    $mergedItems[] = array_merge($item, $firstJoinItem);
                    continue;
                }
            }
        }
        
        return array_values($mergedItems);       
    }
    
    /**
     * Apply wheres clauses to items.
     *
     * @param array $items The items
     * @param array $wheres [[type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and'], ...]
     * @return array The items
     */    
    protected function applyWheres(array $items, array $wheres): array
    {
        $filtered = $items;
        
        foreach($wheres as $where)
        {
            if ($where['boolean'] === 'or') {              
                $filtered = array_unique(array_merge(
                    $filtered,
                    $this->{"where{$where['type']}"}($items, $where)
                ), SORT_REGULAR);
            } else {
                $filtered = $this->{"where{$where['type']}"}($filtered, $where);
            }
        }
        
        return $filtered;
    }
    
    /**
     * Apply where base clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */    
    protected function whereBase(array $items, array $where): array
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->applyWhereNested($items, $where['column'], $where);
        }
        
        if ($where['value'] instanceof SubQueryWhere) {
            return $this->applyWhereSubquery($items, $where['value'], $where);
        }    

        if (!is_string($where['column'])) {
            return [];
        }    

        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return [];
        }

        if (is_null($operator = $this->verifyOperator($where['operator']))) {
            return [];
        }

        $value = $this->verifyValue($where['value']);
        
        if (is_null($value) && $where['value'] !== null) {
            return [];
        }
        
        return array_filter($items, function($item) use ($column, $value, $operator) {
            $columnOrg = $column;
            
            if ($column->jsonSegments()) {
                $column = $column->withJsonSegments(null);
            }            
            
            if (!array_key_exists($column->column(), $item)) {
                return false;
            }
            
            if (is_null($itemValue = $item[$column->column()])) {
                return false;
            }            
            
            if ($columnOrg->jsonSegments()) {
                $path = implode('.', $columnOrg->jsonSegments());

                try {
                    $itemValue = json_decode($itemValue, true, 512, JSON_THROW_ON_ERROR);
                    $itemValue = Arr::get($itemValue, $path);
                } catch (JsonException|Throwable $e) {
                    return false;
                }
            }

            switch ($operator) {
                case '=':
                    return $itemValue === $value;
                case '!=':
                    return $itemValue !== $value;
                case '>':
                    return $itemValue > $value;
                case '<':
                    return $itemValue < $value;
                case '>=':
                    return $itemValue >= $value;
                case '<=':
                    return $itemValue <= $value;
                case '<>':
                    return $itemValue <> $value;
                case '<=>':
                    $value = $itemValue <=> $value;
                    return $value === 0;
                case 'like':
                    $itemValue = (string)$itemValue;
                    $value = (string)$value;
                    $valueTrimed = trim($value, '%');
                    
                    if (str_starts_with($value, '%') && str_ends_with($value, '%')) {
                        return stripos($itemValue, $valueTrimed) !== false;
                    }
                    
                    if (str_starts_with($value, '%')) {
                        return str_ends_with($itemValue, $valueTrimed);
                    }
                    
                    if (str_ends_with($value, '%')) {
                        return str_starts_with($itemValue, $valueTrimed);
                    }
                    
                    return $itemValue === $value;
                case 'not like':
                    $itemValue = (string)$itemValue;
                    $value = (string)$value;
                    $valueTrimed = trim($value, '%');
                    
                    if (str_starts_with($value, '%') && str_ends_with($value, '%')) {
                        return stripos($itemValue, $valueTrimed) === false;
                    }
                    
                    if (str_starts_with($value, '%')) {
                        return ! str_ends_with($itemValue, $valueTrimed);
                    }
                    
                    if (str_ends_with($value, '%')) {
                        return ! str_starts_with($itemValue, $valueTrimed);
                    }
                    
                    return $itemValue !== $value;
            }
            
            return false;
        });
    }
    
    /**
     * Apply where between clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */    
    protected function whereBetween(array $items, array $where): array
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->applyWhereNested($items, $where['column'], $where);
        }
        
        if ($where['value'] instanceof SubQueryWhere) {
            return $this->applyWhereSubquery($items, $where['value'], $where);
        }    

        if (!is_string($where['column'])) {
            return $items;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return $items;
        }
        
        $min = $where['value'][0] ?? null;
        $max = $where['value'][1] ?? null;
        $not = $where['not'] ?? false;

        if (is_null($min) || is_null($max)){
            return $items;
        }
        
        return array_filter($items, function($item) use ($column, $min, $max, $not) {
            $columnOrg = $column;
            
            if ($column->jsonSegments()) {
                $column = $column->withJsonSegments(null);
            }            
            
            if (!array_key_exists($column->column(), $item)) {
                return false;
            }
            
            if (is_null($itemValue = $item[$column->column()])) {
                return false;
            }
            
            if ($columnOrg->jsonSegments()) {
                $path = implode('.', $columnOrg->jsonSegments());

                try {
                    $itemValue = json_decode($itemValue, true, 512, JSON_THROW_ON_ERROR);
                    
                    if (! Arr::has($itemValue, $path)) {
                        return false;
                    }
                    
                    $itemValue = Arr::get($itemValue, $path);
                    
                    if (is_null($itemValue)) {
                        return $not ? true : false;
                    }
                } catch (JsonException|Throwable $e) {
                    return false;
                }
            }
            
            $matches = $itemValue >= $min && $itemValue <= $max;
            
            return $not ? !$matches : $matches;
        });
    }
    
    /**
     * Apply where not between clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */    
    protected function whereNotBetween(array $items, array $where): array
    {
        $where['not'] = true;
        
        return $this->whereBetween($items, $where);
    }

    /**
     * Apply where column clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */    
    protected function whereColumn(array $items, array $where): array
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->applyWhereNested($items, $where['column'], $where);
        }

        if ($where['value'] instanceof SubQueryWhere) {
            return $this->applyWhereSubquery($items, $where['value'], $where);
        }
        
        if (!is_string($where['column'])) {
            return $items;
        }    

        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return $items;
        }

        if (is_null($operator = $this->verifyOperator($where['operator']))) {
            return $items;
        }
        
        if (is_null($value = $this->queryTables->verifyColumn($where['value']))) {
            return $items;
        }

        return array_filter($items, function($item) use ($column, $value, $operator) {
            
            $columnOrg = $column;
            $valueOrg = $value;
            
            if ($column->jsonSegments()) {
                $column = $column->withJsonSegments(null);
            }
            
            if ($value->jsonSegments()) {
                $value = $value->withJsonSegments(null);
            }            
            
            if (
                !array_key_exists($column->column(), $item)
                || !array_key_exists($value->column(), $item)
            ) {
                return false;
            }
            
            if (is_null($itemValue = $item[$column->column()])) {
                return false;
            }
            
            $value = $item[$value->column()];
            
            if ($columnOrg->jsonSegments()) {
                $path = implode('.', $columnOrg->jsonSegments());

                try {
                    $itemValue = json_decode($itemValue, true, 512, JSON_THROW_ON_ERROR);
                    $itemValue = Arr::get($itemValue, $path);
                } catch (JsonException|Throwable $e) {
                    return false;
                }
            }
            
            if ($valueOrg->jsonSegments()) {
                $path = implode('.', $valueOrg->jsonSegments());

                try {
                    $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $value = Arr::get($value, $path);
                } catch (JsonException|Throwable $e) {
                    return false;
                }
            }
            
            switch ($operator) {
                case '=':
                    return $itemValue === $value;
                case '!=':
                    return $itemValue !== $value;
                case '>':
                    return $itemValue > $value;
                case '<':
                    return $itemValue < $value;
                case '>=':
                    return $itemValue >= $value;
                case '<=':
                    return $itemValue <= $value;
                case '<>':
                    return $itemValue <> $value;
                case '<=>':
                    return $itemValue <=> $value;                 
            }
            
            return false;
        });
    }
    
    /**
     * Apply where is null clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @param bool $not
     * @return array The items
     */    
    protected function whereNull(array $items, array $where, bool $not = false): array
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->applyWhereNested($items, $where['column'], $where);
        }

        if (!is_string($where['column'])) {
            return $items;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return $items;
        }
    
        return array_filter($items, function($item) use ($column, $not) {
            $columnOrg = $column;
            
            if ($column->jsonSegments()) {
                $column = $column->withJsonSegments(null);
            }            
            
            if (!array_key_exists($column->column(), $item)) {
                return $not ? false : true;
            }
            
            $itemValue = $item[$column->column()];        
            
            if ($columnOrg->jsonSegments()) {
                $path = implode('.', $columnOrg->jsonSegments());

                try {
                    $itemValue = json_decode($itemValue, true, 512, JSON_THROW_ON_ERROR);
                    $itemValue = Arr::get($itemValue, $path);
                } catch (JsonException|Throwable $e) {
                    return $not ? false : true;
                }
            }
            
            $isNull = $itemValue === null ? true : false;
            
            return $not ? !$isNull : $isNull;
        });
    }

    /**
     * Apply where is not null clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */    
    protected function whereNotNull(array $items, array $where): array
    {
        return $this->whereNull($items, $where, true);
    }
    
    /**
     * Apply where in clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */    
    protected function whereIn(array $items, array $where): array
    {
        if ($where['column'] instanceof SubQueryWhere) {
            return $this->applyWhereNested($items, $where['column'], $where);
        }

        if ($where['value'] instanceof SubQueryWhere) {
            return $this->applyWhereSubquery($items, $where['value'], $where);
        }    

        if (!is_string($where['column'])) {
            return $items;
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return $items;
        }

        if (!in_array($where['operator'], ['IN', 'NOT IN'])) {
            return $items;
        }

        if (!is_array($where['value']) || empty($where['value'])) {
            return $items;
        }
        
        $operator = $where['operator'];
        $values = array_values($where['value']);
        
        return array_filter($items, function($item) use ($column, $values, $operator) {            
            $columnOrg = $column;
            
            if ($column->jsonSegments()) {
                $column = $column->withJsonSegments(null);
            }
            
            if (!array_key_exists($column->column(), $item)) {
                return false;
            }
            
            $itemValue = $item[$column->column()];
            
            if ($columnOrg->jsonSegments()) {
                $path = implode('.', $columnOrg->jsonSegments());

                try {
                    $itemValue = json_decode($itemValue, true, 512, JSON_THROW_ON_ERROR);
                    $itemValue = Arr::get($itemValue, $path);
                } catch (JsonException|Throwable $e) {
                    return false;
                }
            }            
            
            $exists = in_array($itemValue, $values);
            
            return $operator === 'IN' ? $exists : !$exists;
        });
    }
    
    /**
     * Apply where json contains clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */
    protected function whereJsonContains(array $items, array $where): array
    {
        if (!is_string($where['column'])) {
            return [];
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return [];
        }

        if (!in_array($where['operator'], ['=', '!='])) {
            return [];
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return [];
        }
        
        $value = $where['value'];
        $path = null;
        
        if (!empty($column->jsonSegments())) {
            $path = implode('.', $column->jsonSegments());
        }
        
        return array_filter($items, function($item) use ($column, $path, $value) {
            
            $column = $column->withJsonSegments(null);
            $column = $column->column();
            
            if (!array_key_exists($column, $item)) {
                return false;
            }
            
            if (is_null($item[$column])) {
                return false;
            }
            
            try {
                $columnValue = json_decode($item[$column], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException|Throwable $e) {
                $columnValue = $item[$column];
            }
            
            if (is_array($columnValue)) {
                
                if (!is_null($path)) {
                    $columnValue = Arr::get($columnValue, $path);
                }
                
                if (is_array($columnValue) && is_array($value)) {
                    return empty(array_diff($value, $columnValue));
                }
                
                if (is_array($columnValue)) {
                    return empty(array_diff_assoc([$value], $columnValue));
                }
                
                return $columnValue === $value;
            }
            
            return $columnValue === $value;
        });
    }
    
    /**
     * Apply where json contains key clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */
    protected function whereJsonContainsKey(array $items, array $where): array
    {
        if (!is_string($where['column'])) {
            return [];
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return [];
        }

        if (!in_array($where['operator'], ['=', '!='])) {
            return [];
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return [];
        }
        
        if (empty($column->jsonSegments())) {
            return $items;
        }
        
        $path = implode('.', $column->jsonSegments());
        
        return array_filter($items, function($item) use ($column, $path) {
            
            $column = $column->withJsonSegments(null);
            $column = $column->column();
            
            if (!array_key_exists($column, $item)) {
                return false;
            }
            
            if (is_null($item[$column])) {
                return false;
            }
            
            try {
                $columnValue = json_decode($item[$column], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException|Throwable $e) {
                $columnValue = $item[$column];
            }
            
            if (is_array($columnValue)) {
                return Arr::has($columnValue, $path);
            }
            
            return false;
        });
    }
    
    /**
     * Apply where json length clause.
     *
     * @param array $items The items
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items
     */
    protected function whereJsonLength(array $items, array $where): array
    {
        if (!is_string($where['column'])) {
            return [];
        }    
        
        if (is_null($column = $this->queryTables->verifyColumn($where['column']))) {
            return [];
        }

        if (is_null($operator = $this->verifyOperator($where['operator']))) {
            return [];
        }
        
        if (!in_array($where['boolean'], ['and', 'or'])) {
            return [];
        }
        
        $value = $where['value'];
        $path = null;
        
        if (!empty($column->jsonSegments())) {
            $path = implode('.', $column->jsonSegments());
        }
        
        return array_filter($items, function($item) use ($column, $path, $value, $operator) {
            
            $column = $column->withJsonSegments(null);
            $column = $column->column();
            
            if (!array_key_exists($column, $item)) {
                return false;
            }
            
            if (is_null($item[$column])) {
                return false;
            }
            
            try {
                $columnValue = json_decode($item[$column], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException|Throwable $e) {
                $columnValue = $item[$column];
            }
            
            if (!is_array($columnValue)) {
                return false;
            }
            
            if (!is_null($path)) {
                $columnValue = Arr::get($columnValue, $path);
            }
            
            if (!is_array($columnValue)) {
                return false;
            }
            
            switch ($operator) {
                case '=':
                    return count($columnValue) === $value;
                case '!=':
                    return count($columnValue) !== $value;
                case '>':
                    return count($columnValue) > $value;
                case '<':
                    return count($columnValue) < $value;
                case '>=':
                    return count($columnValue) >= $value;
                case '<=':
                    return count($columnValue) <= $value;
                case '<>':
                    return count($columnValue) <> $value;
                case '<=>':
                    $value = count($columnValue) <=> $value;
                    return $value === 0;
            }            
            
            return false;
        });
    }    

    /**
     * Apply where Raw clause.
     *
     * @param array $items The items.
     * @param array $where [type' => 'Base', 'column' => 'colname', 'value' => 'foo', 'operator' => '=', 'boolean' => 'and']
     * @return array The items.
     */    
    protected function whereRaw(array $items, array $where): array
    {
        // or set it on the storage?
        throw new GrammarException('whereRaw() is not supported!');
    }
    
    /**
     * Apply a nested where clause.
     *
     * @param array $items
     * @param SubQueryWhere $query
     * @param array $where The where parameters
     * @return array The items
     */    
    protected function applyWhereNested(array $items, SubQueryWhere $query, array $where): array
    {
        call_user_func($query->callback(), $query->storage());

        if (!empty($query->storage()->getTable())) {
            return $this->applyWhereSubqueryValued($items, $query, $where);
        }
        
        return $this->applyWheres($items, $query->storage()->getWheres());
    }

    /**
     * Apply a where subquery clause with a value.
     *
     * @param array $items The items
     * @param SubQueryWhere $query
     * @param array $where The where parameters
     * @return array The items
     */    
    protected function applyWhereSubqueryValued(array $items, SubQueryWhere $query, array $where): array
    {
        $value = $this->verifyValue($where['value']);

        if (is_null($value) && $where['value'] !== null){
            return $items;
        }
        
        if ($this->verifyOperator($where['operator']) === null) {
            return $items;
        }
        
        throw new GrammarException('Not Yet Supported!');
    }
    
    /**
     * Apply a where subquery clause.
     *
     * @param array $items The items
     * @param SubQueryWhere $query
     * @param array $where The where parameters
     * @return array The items
     */    
    protected function applyWhereSubquery(array $items, SubQueryWhere $query, array $where): array
    {
        $callback = call_user_func($query->callback(), $query->storage());
        
        if (is_null($this->queryTables->verifyColumn($where['column']))) {
            return $items;
        }
        
        if (
            $this->verifyOperator($where['operator']) === null
            && !in_array($where['operator'], ['IN', 'NOT IN', 'ANY', 'SOME'])
        ) {
            return $items;
        }
        
        if ($callback instanceof SubQuery) {
            throw new GrammarException('Subquery statements are not supported!');            
        }
        
        if (empty($query->storage()->getTable())) {
            return [];
        }
        
        $grammar = $this->createGrammarFromStorage($query->storage());
        
        // get all values from subquery.
        $subItems = $grammar->execute();
        
        $values = [];
                
        foreach($subItems as $subItem) {
            foreach($subItem as $value) {
                $values[] = $value;
            }
        }
        
        // handle Column type
        if ($where['type'] === 'Column')
        {
            $where['type'] = 'Base';
            // Subquery must not returns more than 1 row, so get first value found.
            $values = $values[0] ?? null;
        }
        
        $where['value'] = $values;
        
        // any and some acts like in.
        if (in_array($where['operator'], ['ANY', 'SOME'])) {
            $where['operator'] = 'IN';
        }
        
        return $this->applyWheres($items, [$where]);
    }

    /**
     * Apply groups clauses.
     *
     * @param array $items The items.
     * @param array $groups [['column' => $column, 'type' => 'Base|Raw'], ...]
     * @param array $havings [['column' => $column, 'type' => 'Base|Between', ...], ...]
     * @return array The items.
     */    
    protected function applyGroups(array $items, array $groups, array $havings): array
    {
        if (empty($groups))    {
            return $items;
        }
                
        $grouped = [];
        $verifiedGroupColumns = [];

        foreach ($items as $item)
        {
            $groupKeys = [];

            foreach ($groups as $group)
            {
                if ($group['type'] === 'RAW') {
                    throw new GrammarException('Raw type Group By are not supported!');
                }
                
                if (is_null($column = $this->queryTables->verifyColumn($group['column']))) {
                    continue;
                }
                                
                if ($column->jsonSegments()) {
                    $column = $column->withJsonSegments(null);
                }
                
                $column = $column->column();
                
                $verifiedGroupColumns[$column] = null;
                    
                if (array_key_exists($column, $item)) {
                    $groupKeys[] = $column;
                    $groupKeys[] = $item[$column];
                } else {
                    $groupKeys[] = 'null';
                }
            }

            $groupKey = implode('.', $groupKeys);

            if (!array_key_exists($groupKey,  $grouped)) {
                $grouped[$groupKey] = $item;
            }
        }
        
        return $this->applyHavings(
            array_values($grouped),
            $havings,
            array_keys($verifiedGroupColumns)
        );
    }

    /**
     * Apply havings clauses. Only applies to group by rows.
     *
     * @param array $items The items.
     * @param array $havings [['column' => $column, 'type' => 'Base|Between', ...], ...]
     * @param array $groupColumns The groups columns ['column', ...]
     * @return array The items.
     */    
    protected function applyHavings(array $items, array $havings, array $groupColumns): array
    {
        if (empty($havings)) {
            return $items;
        }
        
        $wheres = [];

        foreach($havings as $having)
        {
            if (!is_string($column = $having['column'])) {
                continue;
            }
            
            if (is_null($column = $this->queryTables->verifyColumn($column))) {
                continue;
            }
            
            if ($column->jsonSegments()) {
                $column = $column->withJsonSegments(null);
            }
            
            // only apply to groups.
            if (!in_array($column->name(), $groupColumns)) {
                continue;
            }
            
            $wheres[] = $having;
        }
        
        return $this->applyWheres($items, $wheres);
    }
        
    /**
     * Apply orders clause.
     *
     * @param array $items The items
     * @param array $orders ['column' => 'name', 'direction' => 'asc']
     * @return array The items ordered
     */    
    protected function applyOrders(array $items, array $orders): array
    {
        if (empty($orders)) {
            return $items;
        }
        
        foreach($orders as $order)
        {
            if (is_null($column = $this->queryTables->verifyColumn($order['column']))) {
                continue;
            }
            
            $path = null;
            
            if ($column->jsonSegments()) {
                $path = implode('.', $column->jsonSegments());
            }            
            
            usort($items, function($a, $b) use ($order, $column, $path) {
                
                $a = $a[$column->name()] ?? 0;
                $b = $b[$column->name()] ?? 0;
                
                if ($path) {
                    if (is_string($a)) {
                        try {
                            $a = json_decode($a, true, 512, JSON_THROW_ON_ERROR);
                            $a = Arr::get($a, $path);
                        } catch (JsonException|Throwable $e) {
                            //
                        }
                    }
                    
                    if (is_string($b)) {
                        try {
                            $b = json_decode($b, true, 512, JSON_THROW_ON_ERROR);
                            $b = Arr::get($b, $path);
                        } catch (JsonException|Throwable $e) {
                            //
                        }
                    }
                }
                
                if (strtolower($order['direction']) === 'desc') {
                    return $b <=> $a;
                }
                
                return $a <=> $b;
            });       
        }
        
        return $items;
    }

    /**
     * Apply limit clause.
     *
     * @param array $items The items.
     * @param null|array $limit [12, 0], [number, offset]
     * @return array The items.
     */    
    protected function applyLimit(array $items, null|array $limit): array
    {
        if (empty($limit)) {
            return $items;
        }
        
        $offset = $limit[1] ?? 0;
        $offset = (int) $offset;
        $limit = (int) $limit[0];
        $itemsCount = count($items);

        if ($offset > $itemsCount) {
            $offset = $itemsCount;
        }
        
        if ($offset < 0) {
            $offset = 0;
        }

        if ($limit > $itemsCount) {
            $limit = $itemsCount;
        }

        return array_slice($items, $offset, $limit, true);
    }
    
    /**
     * Get the statement
     *
     * @return null|string 'SELECT id, date_created FROM products' e.g. or null on failure
     */    
    public function getStatement(): null|string
    {
        if ($this->select !== null) {
            $grammar = (new PdoMySqlGrammar($this->tables))
                ->select($this->select)
                ->table($this->table)
                ->joins($this->joins)
                ->wheres($this->wheres)
                ->groups($this->groups)
                ->havings($this->havings)
                ->orders($this->orders)
                ->limit($this->limit)
                ->bindings($this->getBindings());
            
            $statement = $grammar->getStatement();
            $this->bindings = $grammar->getBindings();
            return $statement;
        }

        if ($this->insert !== null) {
            return (new PdoMySqlGrammar($this->tables))
                ->insert($this->insert)
                ->table($this->table)
                ->bindings($this->getBindings())
                ->getStatement();
        }
    
        if ($this->update !== null) {
            $grammar = (new PdoMySqlGrammar($this->tables))
                ->update($this->update)
                ->table($this->table)
                ->joins($this->joins)
                ->wheres($this->wheres)
                ->groups($this->groups)
                ->havings($this->havings)
                ->orders($this->orders)
                ->limit($this->limit)
                ->bindings($this->getBindings());
            
            $statement = $grammar->getStatement();
            $this->bindings = $grammar->getBindings();
            return $statement;
        }

        if ($this->delete !== null) {
            $grammar = (new PdoMySqlGrammar($this->tables))
                ->delete()
                ->table($this->table)
                ->joins($this->joins)
                ->wheres($this->wheres)
                ->groups($this->groups)
                ->havings($this->havings)
                ->orders($this->orders)
                ->limit($this->limit)
                ->bindings($this->getBindings());
            
            $statement = $grammar->getStatement();
            $this->bindings = $grammar->getBindings();
            return $statement;            
        }
        
        return '';
    }

    /**
     * Create a new grammar from the storage.
     *
     * @param StorageInterface $storage
     * @return static A new instance
     */    
    protected function createGrammarFromStorage(StorageInterface $storage): static
    {        
        return (new static($storage, $this->tables))
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