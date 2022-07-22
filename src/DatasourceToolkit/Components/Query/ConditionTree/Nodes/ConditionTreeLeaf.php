<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ConditionTreeLeaf extends ConditionTree
{
    public function __construct(
        protected string  $field,
        protected string  $operator,
        protected $value = null
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

    public function replaceLeafs(PlainConditionTree|ConditionTree $handler, $bind): ConditionTree
    {
        // TODO: Implement replaceLeafs() method.
    }

    public function replaceLeafsAsync(PlainConditionTree|ConditionTree $handler, $bind): ConditionTree
    {
        // TODO: Implement replaceLeafsAsync() method.
    }

    public function match(array $record, Collection $collection, string $timezone): bool
    {
        // TODO: Implement match() method.
    }

    public function forEachLeaf(PlainConditionTree|ConditionTree $handler): void
    {
        // TODO: Implement forEachLeaf() method.
    }

    public function everyLeaf(PlainConditionTree|ConditionTree $handler): bool
    {
        // TODO: Implement everyLeaf() method.
    }

    public function someLeaf(PlainConditionTree|ConditionTree $handler): bool
    {
        // TODO: Implement someLeaf() method.
    }

    public function getProjection(): Projection
    {
        // TODO: Implement getProjection() method.
    }
}
