<?php

namespace ForestAdmin\AgentPHP\Agent\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ValidationError extends HttpException
{
    public function __construct(string $message, array $headers = [], protected string $name = 'ValidationError')
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, $message, null, $headers);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
