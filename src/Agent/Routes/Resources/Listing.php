<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
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

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->list(
                QueryStringParser::parseCaller($this->request),
                $this->paginatedFilter,
                QueryStringParser::parseProjection($this->collection, $this->request)
            ),
        ];
    }
}
