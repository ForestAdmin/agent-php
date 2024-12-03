<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

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
        $this->permissions->can('delete', $this->collection);

        $id = Id::unpackId($this->collection, $args['id']);
        $filter = new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    ConditionTreeFactory::matchIds($this->collection, [$id]),
                ]
            )
        );

        $this->collection->delete($this->caller, $filter);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    public function handleRequestBulk(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('delete', $this->collection);
        $selectionIds = Id::parseSelectionIds($this->collection, $this->request);
        $conditionTreeIds = ConditionTreeFactory::matchIds($this->collection, $selectionIds['ids']);
        if ($selectionIds['areExcluded']) {
            $conditionTreeIds = $conditionTreeIds->inverse();
        }

        $filter = new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    $conditionTreeIds,
                ]
            )
        );

        $this->collection->delete($this->caller, $filter);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
