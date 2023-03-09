<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContextSingle;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;

class ActionCollection extends CollectionDecorator
{
    public function addAction(string $name, BaseAction $action)
    {
        $this->actions[$name] = $action;
    }

    public function execute(Caller $caller, string $name, array $data, ?Filter $filter = null): array
    {
        if (! isset($this->actions[$name])) {
            return $this->childCollection->execute($caller, $name, $data, $filter);
        }

        /** @var BaseAction $action */
        $action = $this->actions[$name];
        $context = $this->getContext($caller, $action, $data, $filter);
        $resultBuilder = new ResultBuilder();
        $result = $action->callExecute($context, $resultBuilder);

        return $result ?? $resultBuilder->success();
    }

    public function getForm(Caller $caller, string $name, ?array $data = null, ?Filter $filter = null): array
    {
        /** @var BaseAction $action */
        $action = $this->actions[$name] ?? null;

        if (! $action) {
            return $this->childCollection->getForm($caller, $name, $data, $filter);
        }

        if ($action->getForm() === []) {
            return [];
        }

        $formValues = $data ?: [];
        $used = [];
        $context = $this->getContext($caller, $action, $formValues, $filter, $used);

        $dynamicFields = $action->getForm();
        $dynamicFields = $this->dropDefaults($context, $dynamicFields, empty($data), $formValues);
        $dynamicFields = $this->dropIfs($context, $dynamicFields);
        $fields = $this->dropDeferred($context, $dynamicFields);
        $used = $context->getUsed();

        /** @var ActionField $field */
        foreach ($fields as $field) {
            // customer did not define a handler to rewrite the previous value => reuse current one.
            if (null === $field->getValue()) {
                $field->setValue($formValues[$field->getLabel()] ?? null);
            }

            // fields that were accessed through the context.formValues.X getter should be watched.
            $field->setWatchChanges(isset($used[$field->getLabel()]));
        }

        return $fields;
    }

    private function getContext(Caller $caller, BaseAction $action, array $formValues = [], ?Filter $filter = null, array &$used = []): ActionContext
    {
        $filter = $filter ? new PaginatedFilter($filter->getConditionTree(), $filter->getSearch(), $filter->getSearchExtended(), $filter->getSegment()) : new PaginatedFilter();
        if ($action->getScope() === ActionScope::SINGLE) {
            return new ActionContextSingle($this, $caller, $filter, $formValues, $used);
        } else {
            return new ActionContext($this, $caller, $filter, $formValues, $used);
        }
    }

    private function dropDefaults(ActionContext $context, array $fields, bool $isFirstCall, array &$data): array
    {
        if ($isFirstCall) {
            $defaults = collect($fields)->map(fn ($field) => $this->evaluate($context, $field->getDefaultValue()));

            foreach ($fields as $index => $field) {
                $data[$field->getLabel()] = $defaults[$index];
            }
        }

        return $fields;
    }

    private function evaluate(ActionContext $context, $value)
    {
        return is_callable($value) ? $value($context) : $value;
    }

    private function dropIfs(ActionContext $context, array $fields): array
    {
        /**
         * @var DynamicField $field
         */
        foreach ($fields as $key => &$field) {
            $ifValue = $this->evaluate($context, $field->getIf());
            if ($ifValue !== null && ! $ifValue) {
                unset($fields[$key]);
            } else {
                $field->setIf(null);
            }
        }

        return $fields;
    }

    private function dropDeferred(ActionContext $context, array $fields): array
    {
        $newFields = [];


        foreach ($fields as $field) {
            foreach ($field->keys() as $key) {
                $field->__set($key, $this->evaluate($context, $field->__get($key)));
            }
            $newFields[] = ActionField::buildFromDynamicField($field);
        }

        return $newFields;
    }
}
