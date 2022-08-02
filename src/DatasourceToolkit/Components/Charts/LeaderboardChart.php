<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class LeaderboardChart extends Chart
{
    public function __construct(protected array $data)
    {
    }

    public function serialize(): array
    {
        $result = collect($this->data)->each(
            fn ($item) => ['key' => $item['key'], 'value' => $item['value']]
        );

        return $result->toArray();
    }
}
