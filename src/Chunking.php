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
 
namespace Tobento\Service\Storage;

use Tobento\Service\Iterable\ChunkIterator;
use BadMethodCallException;
use Generator;

/**
 * Chunking
 */
class Chunking
{
    /**
     * Create a new Chunking.
     *
     * @param StorageInterface $storage
     * @param int $chunkLength
     */
    public function __construct(
        protected StorageInterface $storage,
        protected int $chunkLength = 10000,
    ) {}
    
    /**
     * Set the chunk length.
     *
     * @param int $length
     * @return static $this
     */
    public function chunk(int $length): static
    {
        $this->chunkLength = $length;
        return $this;
    }
    
    /**
     * Get column's value from the items.
     *
     * @param string $column The column name
     * @param null|string $key The column name for the index
     * @return Generator
     */
    public function column(string $column, null|string $key = null): Generator
    {
        $offset = 0;
        
        while (true) {
            $storage = clone $this->storage;
            
            $storage->limit(number: $this->chunkLength, offset: $offset);
            
            $item = $storage->column($column, $key);
            
            yield from $item;
            
            $offset = $offset+$this->chunkLength;
            
            if ($item->count() < $this->chunkLength) {
                return;
            }
        }
    }
    
    /**
     * Returns the items in generator mode.
     *
     * @return Generator
     */
    public function get(): Generator
    {
        $offset = 0;
        
        while (true) {
            
            $storage = clone $this->storage;
            
            $storage->limit(number: $this->chunkLength, offset: $offset);
            
            $items = $storage->get();
            
            yield from $items;
            
            $offset = $offset+$this->chunkLength;
            
            if ($items->count() < $this->chunkLength) {
                return;
            }
        }
    }
    
    /**
     * Insert items.
     *
     * @param iterable $items
     * @param null|array $return The columns to be returned.
     * @return Generator|ItemsInterface
     */
    public function insertItems(iterable $items, null|array $return = []): Generator|ItemsInterface
    {
        if (is_null($return)) {
            $itemsCount = 0;

            foreach(new ChunkIterator($items, $this->chunkLength) as $chunkItems)
            {
                $insertedItems = $this->storage->insertItems(items: $chunkItems, return: $return);
                $itemsCount = $itemsCount + $insertedItems->count();
            }

            return new Items(action: 'insertItems', itemsCount: $itemsCount);
        }
        
        return $this->genInsertItems($items, $return);
    }
    
    /**
     * Insert items in generator mode.
     *
     * @param iterable $items
     * @param null|array $return The columns to be returned.
     * @return Generator
     */
    protected function genInsertItems(
        iterable $items,
        null|array $return = []
    ): Generator {
        
        foreach(new ChunkIterator($items, $this->chunkLength) as $chunkItems)
        {
            yield from $this->storage->insertItems(
                items: $chunkItems,
                return: $return
            );
        }
    }
    
    /**
     * Handle dynamic method calls.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        $methods = [
            'fetchItems', 'storeItems', 'find', 'first', 'value',
            'count', 'insert', 'update', 'updateOrInsert', 'delete'
        ];
        
        if (in_array($name, $methods)) {
            throw new BadMethodCallException('Method "'.$name.'" is not supported in chunking');
        }
        
        // call the storage.
        if (method_exists($this->storage, $name)) {
            call_user_func_array([$this->storage, $name], $arguments);
            return $this;
        }

        throw new BadMethodCallException('Method "'.$name.'" does not exist on "'.static::class.'".');
    }
}