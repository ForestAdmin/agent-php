<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;

class Update extends CollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.update',
            'put',
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
            'content'           => $this->collection->update($this->paginatedFilter, $args['id'], $this->request->get('data')),
        ];
    }
}
