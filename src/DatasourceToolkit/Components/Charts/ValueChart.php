<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class ValueChart extends Chart
{
    public function __construct(protected int $value, protected ?int $previousValue = null)
    {
    }

    public function serialize(): array
    {
        return [
            'countCurrent'  => $this->value,
            'countPrevious' => $this->previousValue,
        ];
    }
}
