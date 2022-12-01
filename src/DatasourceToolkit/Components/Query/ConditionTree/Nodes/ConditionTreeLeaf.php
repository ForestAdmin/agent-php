<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeEquivalent;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
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

    public function replaceLeafs(Closure $handler): ConditionTree
    {
        $result = $handler($this);

        return $result instanceof ConditionTree ? $result : ConditionTreeFactory::fromArray($result);
    }

    public function match(array $record, CollectionContract $collection, string $timezone): bool
    {
        $fieldValue = RecordUtils::getFieldValue($record, $this->field);
        $columnType = $collection->getFields()->get($this->field)->getColumnType();

        switch ($this->operator) {
            case Operators::EQUAL:
                return $fieldValue === $this->value;
            case Operators::LESS_THAN:
                return $fieldValue < $this->value;
            case Operators::GREATER_THAN:
                return $fieldValue > $this->value;
            case Operators::LIKE:
                return $this->like($fieldValue, $this->value, true);
            case Operators::ILIKE:
                return $this->like($fieldValue, $this->value, false);
            case Operators::LONGER_THAN:
                return is_string($fieldValue) ? strlen($fieldValue) > $this->value : false;
            case Operators::SHORTER_THAN:
                return is_string($fieldValue) ? strlen($fieldValue) < $this->value : false;
            case Operators::INCLUDES_ALL:
                return collect(explode(',', $this->value))->every(fn ($v) => in_array($v, $fieldValue, true));
            case Operators::NOT_EQUAL:
            case Operators::NOT_CONTAINS:
                return ! $this->inverse()->match($record, $collection, $timezone);
            default:
                return ConditionTreeEquivalent::getEquivalentTree(
                    $this,
                    Operators::getUniqueOperators(),
                    $columnType,
                    $timezone,
                )->match($record, $collection, $timezone);
        }
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

    private function like(string $value, string $pattern, bool $caseSensitive): bool
    {
        if (! $value) {
            return false;
        }

        $regexp = Str::of($pattern)->replaceMatches('/([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-])/g', '\\$1')
            ->replaceMatches('/%/g', '.*')
            ->replaceMatches('/_/g', '.');

        return Str::is('^' . $regexp . '$/' . $caseSensitive ? 'g' : 'gi', $value);
    }
}
