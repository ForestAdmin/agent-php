<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContextSingle;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\ActionFieldFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use Illuminate\Support\Collection as IlluminateCollection;

class ActionCollection extends CollectionDecorator
{
    public function addAction(string $name, BaseAction $action)
    {
        $this->actions[$name] = $action;
        $this->markSchemaAsDirty();
    }

    public function getActions(): IlluminateCollection
    {
        return $this->actions;
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

    public function getForm(?Caller $caller, string $name, ?array $data = null, ?Filter $filter = null, ?string $changeField = null): array
    {
        /** @var BaseAction $action */
        $action = $this->actions[$name] ?? null;

        if (! $action) {
            return $this->childCollection->getForm($caller, $name, $data, $filter, $changeField);
        }

        if ($action->getForm() === []) {
            return [];
        }

        $formValues = $data ?: [];
        $used = [];
        $context = $this->getContext($caller, $action, $formValues, $filter, $used, $changeField);

        $dynamicFields = $action->getForm();
        $dynamicFields = $this->dropDefaults($context, $dynamicFields, $formValues);
        $dynamicFields = $this->dropIfs($context, $dynamicFields);
        $fields = $this->dropDeferred($context, $dynamicFields);
        $used = $context->getUsed();

        return $this->setWatchChangesOnFields($formValues, $used, $fields);
    }

    private function setWatchChangesOnFields($formValues, $used, $fields): array
    {
        foreach ($fields as &$field) {
            if ($field->getType() !== 'Layout') {
                if ($field->getValue() === null) {
                    // customer did not define a handler to rewrite the previous value => reuse current one.
                    $field->setValue($formValues[$field->getId()] ?? null);
                }

                // fields that were accessed through the context.formValues.X getter should be watched.
                $field->setWatchChanges(isset($used[$field->getId()]));
            } elseif ($field->getComponent() === 'Row') {
                $subFields = $this->setWatchChangesOnFields($formValues, $used, $field->getFields());
                $field->setFields($subFields);
            } elseif ($field->getComponent() === 'Page') {
                $this->setWatchChangesOnFields($formValues, $used, $field->getElements());
            }
        }

        return $fields;
    }

    private function getContext(?Caller $caller, BaseAction $action, array $formValues = [], ?Filter $filter = null, array &$used = [], ?string $changeField = null): ActionContext
    {
        $filter = $filter ? new PaginatedFilter($filter->getConditionTree(), $filter->getSearch(), $filter->getSearchExtended(), $filter->getSegment()) : new PaginatedFilter();
        if ($action->getScope() === ActionScope::SINGLE) {
            return new ActionContextSingle($this, $caller, $filter, $formValues, $used, $changeField);
        } else {
            return new ActionContext($this, $caller, $filter, $formValues, $used, $changeField);
        }
    }

    private function dropDefaults(ActionContext $context, array $fields, array &$data): array
    {
        foreach ($fields as &$field) {
            if($field->getType() !== 'Layout') {
                $field = $this->dropDefault($context, $field, $data);
            }
        }

        return $fields;
    }

    private function dropDefault(ActionContext $context, $field, &$data)
    {
        if (! array_key_exists($field->getId(), $data)) {
            $data[$field->getId()] = $this->evaluate($context, $field->getDefaultValue());
        }

        $field->setDefaultValue(null);

        return $field;
    }

    private function evaluate(ActionContext $context, $value)
    {
        return is_callable($value) && ! is_string($value) ? $value($context) : $value;
    }

    private function dropIfs(ActionContext $context, array $fields): array
    {
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
            $newFields[] = ActionFieldFactory::build($field);
        }

        return $newFields;
    }
}
