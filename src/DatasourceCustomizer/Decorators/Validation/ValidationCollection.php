<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Validation;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;

class ValidationCollection extends CollectionDecorator
{
    private array $validation = [];

    public function addValidation(string $name, array $validation): void
    {
        FieldValidator::validate($this, $name);

        $field = $this->childCollection->getFields()[$name];

        if ($field->getType() !== 'Column') {
            throw new ForestException('Cannot add validators on a relation, use the foreign key instead');
        }

        if ($field->isReadOnly()) {
            throw new ForestException('Cannot add validators on a readonly field');
        }
        $this->validation[$name] ??= [];
        $this->validation[$name][] = $validation;
        $this->markSchemaAsDirty();
    }

    public function create(Caller $caller, array $data)
    {
        foreach ($data as $record) {
            $this->validate($record, $caller->getTimezone(), true);
        }

        return parent::create($caller, $data);
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $this->validate($patch, $caller->getTimezone(), false);

        parent::update($caller, $filter, $patch);
    }

    private function validate(array $record, string $timezone, bool $allFields): void
    {
        foreach ($this->validation as $name => $rules) {
            if ($allFields || isset($record[$name])) {
                // When setting a field to null, only the "Present" validator is relevant
                $applicableRules = $record[$name] === null ? collect($rules).filter(fn ($r) => $r->getOperator() === Operators::PRESENT) : $rules;

                foreach ($applicableRules as $validator) {
                    $rawLeaf = array_merge(['field' => $name], $validator);
                    /** @var ConditionTreeLeaf $tree */
                    $tree = ConditionTreeFactory::fromArray($rawLeaf);
                    ConditionTreeValidator::validate($tree, $this);

                    if (! $tree->match($record, $this, $timezone)) {
                        $message = "$name failed validation rule :";
                        $rule = $validator['value'] ? $validator['operator'] . '(' . $validator['value'] . ')' : $validator['operator'];

                        throw new ForestException("$message $rule");
                    }
                }
            }
        }
    }
}
