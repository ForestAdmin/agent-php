<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class PercentageChart extends Chart
{
    public function serialize(): array
    {
        return $this->data[0];
    }
}
