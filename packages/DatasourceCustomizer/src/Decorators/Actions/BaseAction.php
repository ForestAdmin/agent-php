<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;

class BaseAction
{
    public function __construct(protected string $scope, protected $execute, protected bool $isGenerateFile = false, protected array $form = [])
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
     * @return array
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
        return collect($this->form)->every(
            fn ($field) => $field->isStatic()
        );
    }
}
