<?php

namespace ForestAdmin\AgentPHP\Agent\Http\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequireApproval extends HttpException
{
    public function __construct(string $message, array $headers = [], protected string $name = 'RequireApproval', protected array $data = [])
    {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, null, $headers);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
