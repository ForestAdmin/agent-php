<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions;

/**
 * @codeCoverageIgnore
 */
class ForestValidationException extends \Exception
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
