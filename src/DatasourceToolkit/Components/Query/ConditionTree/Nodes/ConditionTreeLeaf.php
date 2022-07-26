<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use Closure;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

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
        // TODO: Implement inverse() method.
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

    public function forEachLeaf(Closure $handler): void
    {
        // TODO: Implement forEachLeaf() method.
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
        // TODO: Implement getProjection() method.
    }

    public function override(array $partialConditionTree): ConditionTree
    {
        return ConditionTreeFactory::fromArray(array_merge($this->toArray(), $partialConditionTree));
    }
}
