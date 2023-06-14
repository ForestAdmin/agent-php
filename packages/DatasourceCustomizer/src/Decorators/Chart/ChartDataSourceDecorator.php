<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\AgentCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection as IlluminateCollection;

class ChartDataSourceDecorator extends DatasourceDecorator
{
    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, ChartCollection::class);
    }

    public function addChart(string $name, \Closure $definition): void
    {
        if ($this->getCharts()->contains($name)) {
            throw new ForestException("Chart '$name' already exists.");
        }

        $this->charts[$name] = $definition;
    }

    public function renderChart(Caller $caller, string $name): Chart|array
    {
        if (isset($this->charts[$name])) {
            $chart = $this->charts[$name];

            return $chart(new AgentCustomizationContext($this, $caller), new ResultBuilder());
        }

        return parent::renderChart($caller, $name);
    }

    public function getCharts(): IlluminateCollection
    {
        $myCharts = collect($this->charts)->keys();
        $otherCharts = $this->childDataSource->getCharts();

        $duplicate = $myCharts->first(fn ($name) => $otherCharts->contains($name));
        if ($duplicate) {
            throw new ForestException("Chart '$duplicate' is defined twice.");
        }

        return $otherCharts->merge($myCharts);
    }
}
