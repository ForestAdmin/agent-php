<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions;

/**
 * @codeCoverageIgnore
 */
class ForestException extends \RuntimeException
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = '🌳🌳🌳 ' . $message;
        parent::__construct($message, $code, $previous);
    }
}
