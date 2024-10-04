<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

use function ForestAdmin\config;

use Illuminate\Support\Str;

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
        $collectionName = $this->collection->getName();
        $slug = Str::slug($this->chartName);

        $this->addRoute(
            "forest.chart.$collectionName.get.$slug",
            'get',
            "/_charts/{collectionName}/$slug",
            fn () => $this->handleSmartChart()
        );

        $this->addRoute(
            "forest.chart.$collectionName.post.$slug",
            'post',
            "/_charts/{collectionName}/$slug",
            fn () => $this->handleApiChart()
        );

        if (! config('isProduction')) {
            $url = "/forest/_charts/{collectionName}/$slug";
            Logger::log('Info', "Chart '$this->chartName' was mounted at '$url");
        }

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
        return [
            'content' => $this->collection->renderChart(
                QueryStringParser::parseCaller($this->request),
                $this->chartName,
                Id::unpackId($this->collection, $this->request->input('record_id'))
            ),
        ];
    }
}
