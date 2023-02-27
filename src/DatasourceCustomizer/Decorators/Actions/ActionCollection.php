<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContextSingle;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

class ActionCollection extends CollectionDecorator
{
    public function addAction(string $name, BaseAction $action)
    {
        $this->actions[$name] = $action;
    }

    public function execute(Caller $caller, string $name, array $data, ?Filter $filter = null)
    {
        if (! isset($this->actions[$name])) {
            return $this->childCollection->execute($caller, $name, $data, $filter);
        }

        $action = $this->actions[$name];
        $context = $this->getContext($caller, $action, $data, $filter);
        $resultBuilder = new ResultBuilder();
        $result = $action->callExecute($context, $resultBuilder);

        // todo check value of result
        return $result ?? $resultBuilder->success();
    }

    private function getContext(Caller $caller, BaseAction $action, array $formValues, ?Filter $filter = null, ?string $used = null): ActionContext
    {
        if ($action->getScope() === 'SINGLE') {
            return new ActionContext($this, $caller, $formValues, $filter, $used);
        } else {
            return new ActionContextSingle($this, $caller, $formValues, $filter, $used);
        }
    }
}
