<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Validation;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestValidationException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use Illuminate\Support\Collection as IlluminateCollection;

class ValidationCollection extends CollectionDecorator
{
    private array $validation = [];

    public function addValidation(string $name, array $validation): void
    {
        FieldValidator::validate($this->childCollection, $name);

        $field = $this->childCollection->getFields()[$name] ?? null;

        if ($field?->getType() !== 'Column') {
            throw new ForestException('Cannot add validators on a relation, use the foreign key instead');
        }

        if ($field?->isReadOnly()) {
            throw new ForestException('Cannot add validators on a readonly field');
        }

        $this->validation[$name] ??= [];
        $this->validation[$name][] = $validation;

        $this->markSchemaAsDirty();
    }

    public function create(Caller $caller, array $data)
    {
        $this->validate($data, $caller->getTimezone(), true);

        return parent::create($caller, $data);
    }

    public function update(Caller $caller, Filter $filter, array $patch)
    {
        $this->validate($patch, $caller->getTimezone(), false);

        parent::update($caller, $filter, $patch);
    }

    public function getFields(): IlluminateCollection
    {
        $fields = $this->childCollection->getFields();
        foreach ($this->validation as $name => $rules) {
            $validation = array_merge($fields[$name]->getValidation(), $rules);
            $fields[$name]->setValidation($validation);
        }

        return $fields;
    }

    private function validate(array $record, string $timezone, bool $allFields): void
    {
        foreach ($this->validation as $name => $rules) {
            if ($allFields || array_key_exists($name, $record)) {
                // When setting a field to null, only the "Present" validator is relevant
                $applicableRules = $record[$name] === null ? collect($rules)->filter(fn ($r) => $r['operator'] === Operators::PRESENT) : $rules;

                foreach ($applicableRules as $validator) {
                    $rawLeaf = array_merge(['field' => $name], $validator);
                    /** @var ConditionTreeLeaf $tree */
                    $tree = ConditionTreeFactory::fromArray($rawLeaf);

                    if (! $tree->match($record, $this, $timezone)) {
                        $message = "$name failed validation rule :";
                        if (isset($validator['value'])) {
                            $rule = $validator['operator'] . '(' . (is_array($validator['value']) ? implode(',', $validator['value']) : $validator['value']). ')';
                        } else {
                            $rule = $validator['operator'];
                        }

                        throw new ForestValidationException("$message $rule");
                    }
                }
            }
        }
    }
}
