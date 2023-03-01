<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;

class BaseAction
{
    protected array $form = [];

    protected bool $staticForm = true;

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
     * @return string
     */
    public function getForm(): array
    {
        return $this->form;
    }

    /**
     * @return bool
     */
    public function isGenerateFile(): bool
    {
        return $this->isGenerateFile;
    }

    public function isStaticForm(): bool
    {
        return $this->staticForm;
    }
}
