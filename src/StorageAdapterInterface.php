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

/**
 * StorageAdapterInterface
 */
interface StorageAdapterInterface
{
    /**
     * Returns the adapted storage.
     *
     * @return StorageInterface
     */
    public function adaptedStorage(): StorageInterface;
}