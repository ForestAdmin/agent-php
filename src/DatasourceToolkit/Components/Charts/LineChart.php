<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class LineChart extends Chart
{
    public function __construct(protected array $data)
    {
    }

    public function serialize(): array
    {
        $result = collect($this->data)->each(
            fn ($item) => ['label' => $item['label'], 'values' => $item['values']]
        );

        return $result->toArray();
    }
}
