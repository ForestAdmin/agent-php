<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;

class Listing extends CollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.list',
            'get',
            '/{collectionName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $caller = QueryStringParser::parseCaller($this->request);
        $permission = new Permissions($caller);
        $permission->can('browse:' . $this->collection->getName(), $this->collection->getName());
        $scope = $permission->getScope($this->collection);
        $this->paginatedFilter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->list(
                $caller,
                $this->filter,
                QueryStringParser::parseProjection($this->collection, $this->request)
            ),
        ];
    }
}
