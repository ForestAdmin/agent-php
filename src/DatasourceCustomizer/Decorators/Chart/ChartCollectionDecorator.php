<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ChartCollectionDecorator extends CollectionDecorator
{
    private array $charts = [];

    public function addChart(string $name, array $definition): void
    {
        if (isset($this->charts[$name])) {
            throw new ForestException("Chart '$name' already exists.");
        }

        $this->charts[$name] = $definition;
    }
    /*
      override async renderChart(caller: Caller, name: string, recordId: CompositeId): Promise<Chart> {
        if (this.charts[name]) {
          const context = new CollectionChartContext(this, caller, recordId);
          const resultBuilder = new ResultBuilder();

          return this.charts[name](context, resultBuilder);
        }

        return this.childCollection.renderChart(caller, name, recordId);
      }

      protected override refineSchema(subSchema: CollectionSchema): CollectionSchema {
        return {
          ...subSchema,
          charts: [...subSchema.charts, ...Object.keys(this.charts)],
        };
      }
     */
}
