<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;

class BaseAction
{
    protected ?bool $generateFile = null;

    protected array $form = [];

    public function __construct(protected string $scope, protected \Closure $execute)
    {
    }

    public function callExecute(ActionContext $context, ResultBuilder $resultBuilder)
    {
        return $this->execute($context, $resultBuilder);
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    // generateFile?: boolean;
    //  scope: Scope;
    //  form?: DynamicField<Context>[];
    //  execute(
    //    context: Context,
    //    resultBuilder: ResultBuilder,
    //  ): void | ActionResult | Promise<void> | Promise<ActionResult>;
}
