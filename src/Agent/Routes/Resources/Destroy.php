<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\BodyParser;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;

class Destroy extends AbstractCollectionRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.destroy',
            'delete',
            '/{collectionName}/{id}',
            fn ($args) => $this->handleRequest($args)
        );

        $this->addRoute(
            'forest.destroy_bulk',
            'delete',
            '/{collectionName}',
            fn ($args) => $this->handleRequestBulk($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('delete:' . $this->collection->getName(), $this->collection->getName());
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);
        $this->collection->delete($this->caller, $this->filter, $args['id']);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    public function handleRequestBulk(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('delete:' . $this->collection->getName(), $this->collection->getName());
        $scope = $this->permissions->getScope($this->collection);
        $this->filter = ContextFilterFactory::build($this->collection, $this->request, $scope);
        $selectionIds = BodyParser::parseSelectionIds($this->collection, $this->request);
        $this->collection->deleteBulk($this->caller, $this->filter, $selectionIds['areExcluded'], $selectionIds['ids']);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
