<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class PercentageChart extends Chart
{
    public function __construct(protected int|float $value)
    {
    }

    public function serialize()
    {
        return $this->value;
    }
}
