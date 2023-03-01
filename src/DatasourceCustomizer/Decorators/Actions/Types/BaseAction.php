<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;

class BaseAction
{
    protected array $form = [];

    public function __construct(protected string $scope, protected \Closure $execute, protected bool $isGenerateFile = false)
    {
    }

    public function callExecute(ActionContext $context, ResultBuilder $resultBuilder)
    {
        return call_user_func($this->execute, $context, $resultBuilder);
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isGenerateFile(): bool
    {
        return $this->isGenerateFile;
    }

    public function hasForm(): bool
    {
        return ! empty($this->form);
    }
}
