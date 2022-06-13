<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

class ConditionTreeLeaf extends ConditionTree
{
    public function __construct(
        protected string  $field,
        protected string  $operator,
        protected ?string $value = null
    ) {
        if ($this->value) {
            $this->validOperator($value);
        }
    }

    /**
     * @throws \Exception
     */
    public function validOperator(string $value): void
    {
        if (! in_array($value, Operators::ALL_OPERATORS, true)) {
            throw new \Exception("Invalid operators, the $value operator does not exist.");
        }
    }
}
