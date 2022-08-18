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
        $this->permissions->can('edit:' . $this->collection->getName(), $this->collection->getName());

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->update($this->filter, $args['id'], $this->request->get('data')),
        ];
    }
}
