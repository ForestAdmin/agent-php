<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class PieChart extends Chart
{
    public function __construct(protected array $data)
    {
    }

    public function serialize(): array
    {
        return $this->data;
    }
}
