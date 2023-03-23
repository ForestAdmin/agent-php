<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\AgentCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ChartDataSourceDecorator extends DatasourceDecorator
{
    private array $charts = [];

    public function __construct(DatasourceContract|DatasourceDecorator $childDataSource)
    {
        parent::__construct($childDataSource, ChartCollectionDecorator::class);
    }

    public function addChart(string $name, array $definition): void
    {
        if (isset($this->charts[$name])) {
            throw new ForestException("Chart '$name' already exists.");
        }

        $this->charts[$name] = $definition;
    }

    public function renderChart(Caller $caller, string $name): Chart
    {
        if (isset($this->charts[$name])) {
            $chart = $this->charts[$name];

            return $chart(new AgentCustomizationContext($this, $caller), new ResultBuilder());
        }

        parent::renderChart($caller, $name);
    }

    /*
      override get schema(): DataSourceSchema {
        const myCharts = Object.keys(this.charts);
        const otherCharts = this.childDataSource.schema.charts;

        const duplicate = myCharts.find(name => otherCharts.includes(name));
        if (duplicate) throw new Error(`Chart '${duplicate}' is defined twice.`);

        return { ...this.childDataSource.schema, charts: [...myCharts, ...otherCharts] };
      }
     */
}
