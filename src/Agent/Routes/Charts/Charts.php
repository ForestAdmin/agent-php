<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Charts;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;

use function ForestAdmin\cache;

class Charts extends AbstractRoute
{
    public function __construct(
        ForestAdminHttpDriverServices $services,
    ) {
        parent::__construct($services);
    }

    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest.apiChart',
            'get',
            '/_charts/{chartName}',
            fn () => $this->handleApiChart()
        );

        $this->addRoute(
            'forest.smartChart',
            'post',
            '/_charts/{chartName}',
            fn () => $this->handleSmartChart()
        );

        return $this;
    }

    public function handleApiChart()
    {
        //ForestAdminHttpDriver::sendSchema(cache('datasource'));

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    public function handleSmartChart()
    {
        //ForestAdminHttpDriver::sendSchema(cache('datasource'));

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
