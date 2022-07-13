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
        $datasource = cache('datasource');
        /** @var Collection $collection */
        $collection = $datasource->getCollection($args['collectionName']);
        $collection->hydrate($args);
        $request = Request::createFromGlobals();
        $scope = null;

        $paginatedFilter = ContextFilterFactory::buildPaginated($collection, $request, $scope);

        return [
            'content' => [
                'count' => $collection->count($paginatedFilter),
            ],
        ];
    }
}
