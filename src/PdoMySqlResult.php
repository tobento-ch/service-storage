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

use Tobento\Service\Storage\Grammar\PdoMySqlGrammar;
use Pdo;

/**
 * PdoMySqlResult
 */
class PdoMySqlResult extends Result
{
    /**
     * Create a new PdoMySqlResult.
     *
     * @param string $action The action such as insert.
     * @param ItemInterface $item The item.
     * @param ItemsInterface $items The items.
     * @param null|PdoMySqlGrammar $query
     * @param null|Pdo $pdo
     * @param null|int $itemsCount The items count.
     */    
    public function __construct(
        protected string $action,
        protected ItemInterface $item,
        protected ItemsInterface $items,
        protected null|PdoMySqlGrammar $query = null,
        protected null|Pdo $pdo = null,
        protected null|int $itemsCount = null
    ) {}
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function items(): ItemsInterface
    {
        if (!is_null($this->query) && !is_null($this->pdo))
        {
            $pdoStatement = $this->pdo->prepare($this->query->getStatement());
            $pdoStatement->execute($this->query->getBindings());

            $this->items = new Items($pdoStatement->fetchAll(PDO::FETCH_ASSOC));
            $this->query = null;
        }
        
        return $this->items;
    }
}