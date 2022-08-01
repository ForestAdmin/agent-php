<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class TimeBasedChart extends Chart
{
    public function serialize(): array
    {
        collect($this->data)->each(
            fn ($item) => ['label' => $item['label'], 'values' => $item['values']]
        );

        return $this->toArray();
    }
}
