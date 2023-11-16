<?php

namespace ForestAdmin\AgentPHP\Agent\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @codeCoverageIgnore
 */
class AuthenticationOpenIdClient extends HttpException
{
    public function __construct(protected string $error, protected string $errorDescription, protected string $state, array $headers = [])
    {
        parent::__construct(Response::HTTP_UNAUTHORIZED, $this->errorDescription, null, $headers);
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getErrorDescription(): string
    {
        return $this->errorDescription;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
