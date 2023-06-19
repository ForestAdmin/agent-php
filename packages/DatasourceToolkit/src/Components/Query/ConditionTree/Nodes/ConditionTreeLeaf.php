<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeEquivalent;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class ConditionTreeLeaf extends ConditionTree
{
    public function __construct(
        protected string $field,
        protected string $operator,
        protected        $value = null
    ) {
        if ($this->operator) {
            $this->validOperator($this->operator);
        }
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue()
    {
        return $this->value;
    }

    #[ArrayShape(['field' => "string", 'operator' => "string", 'value' => "null"])]
    public function toArray(): array
    {
        return [
            'field'    => $this->field,
            'operator' => $this->operator,
            'value'    => $this->value,
        ];
    }

    /**
     * @throws ForestException
     */
    public function validOperator(string $value): void
    {
        if (! in_array($value, Operators::getAllOperators(), true)) {
            throw new ForestException("Invalid operators, the $value operator does not exist.");
        }
    }

    public function inverse(): ConditionTree
    {
        if (in_array('Not_' . $this->getOperator(), Operators::getAllOperators(), true)) {
            return $this->override(operator: 'Not_' . $this->getOperator());
        }

        if (Str::startsWith($this->getOperator(), 'Not')) {
            return $this->override(operator: Str::substr($this->getOperator(), 4));
        }

        return match ($this->getOperator()) {
            'Blank'   => $this->override(operator: 'Present'),
            'Present' => $this->override(operator: 'Blank'),
            default   => throw new ForestException('Operator: ' . $this->getOperator() . ' cannot be inverted.'),
        };
    }

    public function replaceLeafs(Closure $handler): ?ConditionTree
    {
        $result = $handler($this);

        if ($result === null) {
            return null;
        }

        return $result instanceof ConditionTree ? $result : ConditionTreeFactory::fromArray($result);
    }

    public function match(array $record, CollectionContract $collection, string $timezone): bool
    {
        $fieldValue = RecordUtils::getFieldValue($record, $this->field);
        $columnType = CollectionUtils::getFieldSchema($collection, $this->field)->getColumnType();
        $supported = [
            Operators::IN, Operators::EQUAL, Operators::LESS_THAN, Operators::GREATER_THAN, Operators::MATCH, Operators::STARTS_WITH, Operators::ENDS_WITH,
            Operators::LONGER_THAN, Operators::SHORTER_THAN, Operators::INCLUDES_ALL, Operators::NOT_IN, Operators::NOT_EQUAL, Operators::NOT_CONTAINS,
        ];

        return match ($this->operator) {
            Operators::IN           => collect(is_array($this->value) ? $this->value : explode(',', $this->value))
                ->contains($fieldValue),
            Operators::EQUAL        => $fieldValue === $this->value,
            Operators::LESS_THAN    => $fieldValue < $this->value,
            Operators::GREATER_THAN => $fieldValue > $this->value,
            Operators::MATCH        => is_string($fieldValue) && preg_match($this->value, $fieldValue),
            Operators::STARTS_WITH  => is_string($fieldValue) && Str::startsWith($fieldValue, $this->value),
            Operators::ENDS_WITH    => is_string($fieldValue) && Str::endsWith($fieldValue, $this->value),
            Operators::LONGER_THAN  => is_string($fieldValue) && strlen($fieldValue) > $this->value,
            Operators::SHORTER_THAN => is_string($fieldValue) && strlen($fieldValue) < $this->value,
            Operators::INCLUDES_ALL => collect(is_array($this->value) ? $this->value : explode(',', $this->value))
                ->every(fn ($v) => in_array($v, $fieldValue, true)),
            Operators::NOT_IN, Operators::NOT_EQUAL, Operators::NOT_CONTAINS => ! $this->inverse()->match($record, $collection, $timezone),
            default                 => ConditionTreeEquivalent::getEquivalentTree(
                $this,
                $supported,
                $columnType,
                $timezone,
            )?->match($record, $collection, $timezone),
        };
    }

    public function forEachLeaf(Closure $handler): self
    {
        return $handler($this);
    }

    public function everyLeaf(Closure $handler): bool
    {
        return $handler($this);
    }

    public function someLeaf(Closure $handler): bool
    {
        return $handler($this);
    }

    public function getProjection(): Projection
    {
        return new Projection([$this->field]);
    }

    public function override(...$args): ConditionTree
    {
        return new self(...array_merge($this->toArray(), $args));
    }

    public function useIntervalOperator(): bool
    {
        return in_array($this->operator, Operators::getIntervalOperators());
    }
}
