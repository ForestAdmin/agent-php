<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class DistributionChart extends Chart
{
    public function __construct(
        protected string $key,
        protected int $value,
    ) {
    }

    /**
     * @return mixed
     */
    public function serialize(array $data)
    {
        collect($data)->each(
            fn ($item) => ['key' => $item['key'], 'value' => $item['value']]
        );

        return $data->toArray();
    }
}
