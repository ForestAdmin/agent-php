<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class Store extends CollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.create',
            'post',
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
            'content'           => $this->collection->create($this->request->get('data')),
        ];
    }
}
