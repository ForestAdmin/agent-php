<?php

namespace ForestAdmin\AgentPHP\Agent\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @codeCoverageIgnore
 */
class UnprocessableError extends HttpException
{
    public function __construct(string $message, array $headers = [], protected string $name = 'UnprocessableError')
    {
        parent::__construct(Response::HTTP_UNPROCESSABLE_ENTITY, $message, null, $headers);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
