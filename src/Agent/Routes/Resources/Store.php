<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;

class Store extends AbstractCollectionRoute
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
        $this->permissions->can('add:' . $this->collection->getName());
        $result = $this->collection->create($this->caller, $this->request->get('data'));

        return [
            'name'              => $args['collectionName'],
            'content'           => JsonApi::renderItem($result, $this->collection->makeTransformer(), $args['collectionName']),
        ];
    }
}
