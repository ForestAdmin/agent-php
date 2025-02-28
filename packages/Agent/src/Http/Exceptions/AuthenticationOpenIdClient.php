<?php

namespace ForestAdmin\AgentPHP\Agent\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @codeCoverageIgnore
 */
class AuthenticationOpenIdClient extends HttpException
{
    private string $description;

    public function __construct(int $status = Response::HTTP_UNAUTHORIZED, string $message = 'Authentication failed with OpenID Client', string $description = '', array $headers = [], ?\Throwable $previous = null)
    {
        $this->description = $description;

        parent::__construct($status, $message, $previous, $headers);
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
