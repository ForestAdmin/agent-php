<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class TimeBasedChart extends Chart
{
    public function __construct(
        protected string $label,
        protected array $values,
    ) {
    }

    /**
     * @return mixed
     */
    public function serialize(array $data)
    {
        collect($data)->each(
            fn ($item) => ['label' => $item['label'], 'values' => $item['values']]
        );

        return $data->toArray();
    }
}
