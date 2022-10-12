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

    public function override(...$args): self
    {
        return new self(...array_merge($this->toArray(), $args));
    }

    public function nest(?string $prefix = null): self
    {
        if (null === $prefix) {
            return $this;
        }

        $nestedField = null;
        $nestedGroups = [];

        if ($this->field) {
            $nestedField = "$prefix:$this->field";
        }

        if (count($this->groups) > 0) {
            $nestedGroups = collect($this->groups)->map(fn ($item) => [
                'field'     => $prefix . ':' . $item['field'],
                'operation' => $item['operation'],
            ])->toArray();
        }

        return new self(operation: $this->operation, field: $nestedField, groups: $nestedGroups);
    }

    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'field'     => $this->field,
            'groups'    => $this->groups,
        ];
    }
}
