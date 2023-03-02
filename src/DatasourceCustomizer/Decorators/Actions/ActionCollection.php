<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContextSingle;
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

        /** @var BaseAction $action */
        $action = $this->actions[$name];
        $context = $this->getContext($caller, $action, $data, $filter);
        $resultBuilder = new ResultBuilder();
        $result = $action->callExecute($context, $resultBuilder);

        // todo check value of result
        return $result ?? $resultBuilder->success();
    }

    public function getForm(Caller $caller, string $name, ?array $data = null, ?Filter $filter = null): array
    {
        /** @var BaseAction $action */
        $action = $this->actions[$name] ?? null;

        if (! $action) {
            // todo check base getForm
            return $this->childCollection->getForm($caller, $name, $data, $filter);
        }

        if ($action->getForm() === []) {
            return [];
        }

        $formValues = $data ? [...$data] : $data;
        $used = [];
        $context = $this->getContext($caller, $action, $formValues, $filter, $used);

//        // Convert DynamicField to ActionField in successive steps.
//        let dynamicFields: DynamicField[];
//        dynamicFields = action.form.map(c => ({ ...c }));
//        dynamicFields = await this.dropDefaults(context, dynamicFields, !data, formValues);
//        dynamicFields = await this.dropIfs(context, dynamicFields);
//
//        const fields = await this.dropDeferred(context, dynamicFields);
//
//        for (const field of fields) {
//            // customer did not define a handler to rewrite the previous value => reuse current one.
//            if (field.value === undefined) field.value = formValues[field.label];
//
//            // fields that were accessed through the context.formValues.X getter should be watched.
//            field.watchChanges = used.has(field.label);
//        }
//
//        return fields;
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
