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
            $validation = $this->deduplicate(array_merge($fields[$name]->getValidation(), $rules));
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
                        $rule = ($validator['value'] ?? null) ? $validator['operator'] . '(' . is_array($validator['value']) ? implode(',', $validator['value']) : $validator['value'] . ')' : $validator['operator'];

                        throw new ForestValidationException("$message $rule");
                    }
                }
            }
        }
    }

    /**
     * @param array $rules
     * @return array
     * Deduplicate rules which the frontend understand
     * We ignore other rules as duplications are not an issue within the agent
     */
    private function deduplicate(array $rules): array
    {
        $values = [];

        foreach ($rules as $rule) {
            $values[$rule['operator']] ??= [];
            $values[$rule['operator']][] = $rule;
        }

        // Remove duplicate "Present"
        if (isset($values[Operators::PRESENT])) {
            $values[Operators::PRESENT] = [$values[Operators::PRESENT][0]];
        }

        // Merge duplicate 'GreaterThan', 'After' and 'LongerThan' (keep the max value)
        foreach ([Operators::GREATER_THAN, Operators::AFTER, Operators::LONGER_THAN] as $operator) {
            if (isset($values[$operator])) {
                while (count($values[$operator]) > 1) {
                    $last = array_pop($values[$operator]);

                    $values[$operator][0] = [
                        'operator' => $operator,
                        'value'    => $this->max($last['value'], $values[$operator][0]['value']),
                    ];
                }
            }
        }

        // Merge duplicate 'LessThan', 'Before' and 'ShorterThan' (keep the min value)
        foreach ([Operators::LESS_THAN, Operators::BEFORE, Operators::SHORTER_THAN] as $operator) {
            if (isset($values[$operator])) {
                while (count($values[$operator]) > 1) {
                    $last = array_pop($values[$operator]);

                    $values[$operator][0] = [
                        'operator' => $operator,
                        'value'    => $this->min($last['value'], $values[$operator][0]['value']),
                    ];
                }
            }
        }

        return collect($values)->reduce(fn ($memo, $r) => array_merge($memo, $r), []);
    }

    private function min($valueA, $valueB)
    {
        return $valueA < $valueB ? $valueA : $valueB;
    }

    private function max($valueA, $valueB)
    {
        return $valueA < $valueB ? $valueB : $valueA;
    }
}
