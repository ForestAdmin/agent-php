<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class Show extends CollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.show',
            'get',
            '/{collectionName}/{id}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->show($this->paginatedFilter, $args['id'], new Projection()),
        ];
    }
}
