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
            fn () => $this->handleSmartChart()
        );

        $this->addRoute(
            "forest.chart.post.$this->chartName",
            'post',
            "/_charts/$this->chartName",
            fn () => $this->handleApiChart()
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
        return $this->datasource->renderChart(
            QueryStringParser::parseCaller($this->request),
            $this->chartName
        );
    }
}
