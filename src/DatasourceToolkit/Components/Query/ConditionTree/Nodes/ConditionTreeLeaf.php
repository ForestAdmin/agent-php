<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
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
        if (! in_array($value, Operators::ALL_OPERATORS, true)) {
            throw new ForestException("Invalid operators, the $value operator does not exist.");
        }
    }

    public function inverse(): ConditionTree
    {
        if (in_array('Not' . $this->getOperator(), Operators::ALL_OPERATORS, true)) {
            return $this->override(operator: 'Not' . $this->getOperator());
        }

        if (Str::startsWith($this->getOperator(), 'Not')) {
            return $this->override(operator: Str::substr($this->getOperator(), 3));
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

    public function match(array $record, Collection $collection, string $timezone): bool
    {
        // TODO: Implement match() method.
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
//        return ConditionTreeFactory::fromArray(array_merge($this->toArray(), $partialConditionTree));
    }

    public function useIntervalOperator(): bool
    {
        return in_array($this->operator, Operators::INTERVAL_OPERATORS);
    }
}
