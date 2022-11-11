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

use Exception;
use Throwable;

/**
 * QueryException
 */
class QueryException extends Exception
{
    /**
     * Create a new QueryException.
     *
     * @param string $statement
     * @param array $bindings
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected string $statement,
        protected array $bindings,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        if ($message === '') {
            $message = $this->formatMessage($statement, $bindings, $previous);
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the statement.
     *
     * @return string
     */
    public function statement(): string
    {
        return $this->statement;
    }
    
    /**
     * Returns the bindings.
     *
     * @return array
     */
    public function bindings(): array
    {
        return $this->bindings;
    }
    
    /**
     * Returns the formatted message.
     *
     * @param string $statement
     * @param array $bindings     
     * @return string
     */
    protected function formatMessage(
        string $statement,
        array $bindings,
        null|Throwable $previous = null
    ): string {
        $segments = explode('?', $statement);
        $phrases = [];

        foreach($segments as $key => $phrase) {
            if (array_key_exists($key, $bindings) && is_scalar($bindings[$key])) {
                $phrases[] = $phrase.(string)$bindings[$key];
            } else {
                $phrases[] = $phrase.'?';
            }
        }
        
        return rtrim(implode('', $phrases), '?');
    }    
}