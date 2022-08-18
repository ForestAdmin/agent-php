<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
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
        $this->permissions->can('browse:' . $this->collection->getName(), $this->collection->getName());
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::buildPaginated($this->collection, $this->request, $scope);

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->list(
                $this->filter,
                $this->caller,
                QueryStringParser::parseProjection($this->collection, $this->request)
            ),
        ];
    }
}
