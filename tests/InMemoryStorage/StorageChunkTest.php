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

use PHPUnit\Framework\TestCase;
use Tobento\Service\Storage\InMemoryStorage;

/**
 * StorageChunkTest
 */
class StorageChunkTest extends \Tobento\Service\Storage\Test\StorageChunkTest
{
    public function setUp(): void
    {
        parent::setUp();
        
        $this->storage = new InMemoryStorage([], $this->tables);
    }
}