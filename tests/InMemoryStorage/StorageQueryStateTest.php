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

namespace Tobento\Service\Storage\Test\InMemoryStorage;

use Tobento\Service\Storage\InMemoryStorage;

class StorageQueryStateTest extends \Tobento\Service\Storage\Test\StorageQueryState
{
    public function setUp(): void
    {
        parent::setUp();
        
        $this->storage = new InMemoryStorage([
            'products' => $this->products,
        ], $this->tables);
    }
}