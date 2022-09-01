<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;

class Count extends AbstractCollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.count',
            'get',
            '/{collectionName}/count',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('browse:' . $this->collection->getName(), $this->collection->getName());
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);

        $aggregation = new Aggregation(operation: 'Count');

        return [
            'content' => [
                'count' => $this->collection->aggregate($this->caller, $this->filter, $aggregation),
            ],
        ];
    }
}
