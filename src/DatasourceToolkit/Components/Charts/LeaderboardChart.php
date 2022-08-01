<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class LeaderboardChart extends Chart
{
    public function serialize(): array
    {
        collect($this->data)->each(
            fn ($item) => ['key' => $item['key'], 'value' => $item['value']]
        );

        return $this->data->toArray();
    }
}
