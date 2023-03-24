<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;

class ChartCollectionDecorator extends CollectionDecorator
{
    public function addChart(string $name, \Closure $definition): void
    {
        if (isset($this->charts[$name])) {
            throw new ForestException("Chart '$name' already exists.");
        }

        $this->charts[$name] = $definition;
    }

    public function renderChart(Caller $caller, string $name, array $recordId)
    {
        if (isset($this->charts[$name])) {
            $chart = $this->charts[$name];

            return $chart(new CollectionChartContext($this, $caller, $recordId), new ResultBuilder());
        }

        return $this->childCollection->renderChart($caller, $name, $recordId);
    }

    public function getCharts(): IlluminateCollection
    {
        $myCharts = collect($this->charts)->keys();
        $otherCharts = $this->childCollection->getCharts();

        $duplicate = $myCharts->first(fn ($name) => $otherCharts->contains($name));
        if ($duplicate) {
            throw new ForestException("Chart '$duplicate' is defined twice.");
        }

        return $otherCharts->merge($myCharts);
    }
}
