<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class DistributionChart extends Chart
{
    public function serialize(): array
    {
        collect($this->data)->each(
            fn ($item) => ['key' => $item['key'], 'value' => $item['value']]
        );

        return $this->toArray();
    }
}
