<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class PercentageChart extends Chart
{
    public function __construct(protected int $value)
    {
    }

    public function serialize(): array
    {
        return $this->value;
    }
}
