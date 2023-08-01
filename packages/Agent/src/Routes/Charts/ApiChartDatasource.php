<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;

use function ForestAdmin\config;

use Illuminate\Support\Str;

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
        $slug = Str::slug($this->chartName);
        $this->addRoute(
            "forest.chart.get.$slug",
            'get',
            "/_charts/$slug",
            fn () => $this->handleSmartChart()
        );

        $this->addRoute(
            "forest.chart.post.$slug",
            'post',
            "/_charts/$slug",
            fn () => $this->handleApiChart()
        );

        if (! config('isProduction')) {
            $url = "/forest/_charts/$slug";
            Logger::log('Info', "Chart '$this->chartName' was mounted at '$url");
        }

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
        return [
            'content' => $this->datasource->renderChart(
                QueryStringParser::parseCaller($this->request),
                $this->chartName
            ),
        ];
    }
}
