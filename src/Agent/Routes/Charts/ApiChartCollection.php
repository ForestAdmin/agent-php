<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

class ApiChartCollection extends AbstractAuthenticatedRoute
{
    public function __construct(protected CollectionContract $collection, protected string $chartName)
    {
        parent::__construct();
        $this->setupRoutes();
    }

    public function setupRoutes(): AbstractRoute
    {
        // Mount both GET and POST, respectively for smart and api charts.
        $this->addRoute(
            "forest.chart.collection.get.$this->chartName",
            'get',
            "/_charts/{collectionName}/$this->chartName",
            fn () => $this->handleSmartChart()
        );

        $this->addRoute(
            "forest.chart.collection.post.$this->chartName",
            'post',
            "/_charts/{collectionName}/$this->chartName",
            fn () => $this->handleApiChart()
        );

        return $this;
    }

    public function handleApiChart(): array
    {
        return [
            'content' => JsonApi::renderChart(
                $this->collection->renderChart(
                    QueryStringParser::parseCaller($this->request),
                    $this->chartName,
                    Id::unpackId($this->collection, $this->request->input('record_id'))
                )
            ),
        ];
    }

    public function handleSmartChart(): array
    {
        return $this->collection->renderChart(
            QueryStringParser::parseCaller($this->request),
            $this->chartName,
            Id::unpackId($this->collection, $this->request->input('record_id'))
        );
    }
}
