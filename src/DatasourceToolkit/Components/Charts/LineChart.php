<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class LineChart extends Chart
{
    public function serialize(): array
    {
        collect($this->data)->each(
            fn ($item) => ['label' => $item['label'], 'values' => $item['values']]
        );

        return $this->toArray();
    }
}
