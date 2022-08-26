<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;

class Update extends AbstractCollectionRoute
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
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);

        return [
            'renderTransformer' => true,
            'content'           => $this->collection->update($this->caller, $this->filter, $args['id'], $this->request->get('data')),
        ];
    }
}
