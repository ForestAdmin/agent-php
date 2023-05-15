<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context;

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\UnprocessableError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ValidationError;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class HookContext extends CollectionCustomizationContext
{
    public function throwValidationError(string $message): HttpException
    {
        throw new ValidationError($message);
    }

    public function throwForbiddenError(string $message): HttpException
    {
        throw new ForbiddenError($message);
    }

    public function throwError(string $message): HttpException
    {
        throw new UnprocessableError($message);
    }
}
