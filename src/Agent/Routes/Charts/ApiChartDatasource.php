<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

class ApiChartDatasource extends AbstractAuthenticatedRoute
{
    protected DatasourceContract $datasource;

    public function __construct(protected string $chartName)
    {
        $this->datasource = AgentFactory::get('datasource');
        parent::__construct();
        $this->setupRoutes();
    }

    public function setupRoutes(): AbstractRoute
    {
        // Mount both GET and POST, respectively for smart and api charts.
        $this->addRoute(
            "forest.chart.get.$this->chartName",
            'get',
            "/_charts/$this->chartName",
            fn ($args) => $this->handleSmartChart()
        );

        $this->addRoute(
            "forest.chart.post.$this->chartName",
            'post',
            "/_charts/$this->chartName",
            fn ($args) => $this->handleApiChart()
        );

        return $this;
    }

    public function handleApiChart(): array
    {
        return [
            'content' => JsonApi::renderChart(
                $this->datasource->renderChart(
                    QueryStringParser::parseCaller($this->request),
                    $this->chartName
                )
            ),
        ];
    }

    public function handleSmartChart(): array
    {
        /*
         private async handleSmartChart(context: Context) {
            // Smart charts need the data to be unformatted
            context.response.body = await this.dataSource.renderChart(
              QueryStringParser.parseCaller(context),
              this.chartName,
            );
          }
         */
        return [];
    }
/*


  private async handleApiChart(context: Context) {
    // Api Charts need the data to be formatted in JSON-API
    context.response.body = {
      data: {
        id: uuidv1(),
        type: 'stats',
        attributes: {
          value: await this.dataSource.renderChart(
            QueryStringParser.parseCaller(context),
            this.chartName,
          ),
        },
      },
    };
  }


    */
}
