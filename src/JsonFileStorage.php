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

use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Filesystem\JsonFile;
use Tobento\Service\FileCreator\FileCreator;
use Tobento\Service\Iterable\Iter;

/**
 * JsonFileStorage
 */
class JsonFileStorage extends InMemoryStorage
{
    /**
     * Create a new JsonFileStorage.
     *
     * @param string $dir The storage dir where to store the file. Must end with a slash.
     */    
    public function __construct(
        protected string $dir,
        null|TablesInterface $tables = null,
    ) {
        parent::__construct([], $tables);
    }

    /**
     * Returns the dir.
     *
     * @return string
     */
    public function dir(): string
    {
        return $this->dir;
    }
    
    /**
     * Fetches the table items.
     *
     * @param string $table The table name.
     * @return iterable The items fetched.
     */
    public function fetchItems(string $table): iterable
    {
        if (is_null($table = $this->tables()->verifyTable($table))) {
            return [];
        }
        
        $file = new JsonFile($this->dir.$table->name().'.json');

        return $file->isFile() ? $file->toArray() : [];
    }

    /**
     * Stores the table items.
     *
     * @param string $table The table name.
     * @param iterable $items The items to store.
     * @return iterable The stored items.
     */
    public function storeItems(string $table, iterable $items): iterable
    {
        if (is_null($table = $this->tables()->verifyTable($table))) {
            return [];
        }
        
        if (
            $this->transactionLevel > 0
            && !isset($this->transactionItems[$this->transactionLevel][$table->name()])
        ) {
            $this->transactionItems[$this->transactionLevel][$table->name()] = $this->fetchItems($table->name());
        }        
        
        $fileCreator = new FileCreator();
        $fileCreator->content(json_encode(Iter::toArray(iterable: $items), JSON_UNESCAPED_UNICODE))
                    ->create(
                        $this->dir.$table->name().'.json',
                        $fileCreator::CONTENT_NEW,
                        0644
                    );

        return $items;
    }
}