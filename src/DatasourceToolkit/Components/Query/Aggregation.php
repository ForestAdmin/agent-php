<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class Aggregation
{
    public function __construct(protected string $operation, protected ?string $field = null, protected ?array $groups = [])
    {
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function getProjection()
    {
        $aggregateFields = [];
        if ($this->field) {
            $aggregateFields[] = $this->field;
        }

        if ($this->groups) {
            foreach ($this->groups as $group) {
                $aggregateFields[] = $group['field'];
            }
        }

        return new Projection($aggregateFields);

       
    }
}
