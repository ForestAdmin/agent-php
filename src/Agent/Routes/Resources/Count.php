<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use function ForestAdmin\cache;

class Count extends CollectionRoute
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
        $this->paginatedFilter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);

        return [
            'content' => [
                'count' => $this->collection->count($this->filter),
            ],
        ];
    }
}
