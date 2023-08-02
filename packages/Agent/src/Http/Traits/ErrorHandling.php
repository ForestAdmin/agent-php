<?php

namespace ForestAdmin\AgentPHP\Agent\Http\Traits;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

trait ErrorHandling
{
    public function getErrorStatus(Throwable $error): int
    {
        if ($error instanceof HttpException) {
            return $error->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    public function getErrorMessage(Throwable $error): string
    {
        if ($error instanceof HttpException || is_subclass_of($error, HttpException::class)) {
            return $error->getMessage();
        }

        if ($customizer = AgentFactory::get('customizeErrorMessage')) {
            $message = $customizer($error);
            if ($message) {
                return $message;
            }
        }

        return 'Unexpected error';
    }

    public function getErrorName(Throwable $error): string
    {
        if (method_exists($error, 'getName')) {
            return $error->getName();
        }

        return (new ReflectionClass($error))->getShortName();
    }

    public function getErrorHeaders(Throwable $error): array
    {
        if (method_exists($error, 'getHeaders')) {
            return $error->getHeaders();
        }

        return [];
    }
}
