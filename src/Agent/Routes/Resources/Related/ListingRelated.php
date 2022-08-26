<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Routes\Resources\CollectionRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;

class ListingRelated extends CollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.list',
            'get',
            '/{collectionName}/{id}/relationships/{relationName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->relationName = $this->datasource->getCollection($args['relationName']);

        $id = Id::unpackId($this->collection, $args['id']);

        //$id

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->list(
                QueryStringParser::parseCaller($this->request),
                $this->filter,
                QueryStringParser::parseProjection($this->collection, $this->request)
            ),
        ];
    }
}
