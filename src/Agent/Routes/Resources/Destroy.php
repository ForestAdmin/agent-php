<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractCollectionRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use Illuminate\Support\Arr;

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
        $this->permissions->can('delete:' . $this->collection->getName());

        $id = Id::unpackId($this->collection, $args['id']);
        $filter = ContextFilterFactory::build(
            $this->collection,
            $this->request,
            ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    ConditionTreeFactory::matchIds($this->collection, [$id]),
                ]
            )
        );

        $this->collection->delete($this->caller, $filter, $id);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    public function handleRequestBulk(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('delete:' . $this->collection->getName());
        $selectionIds = Id::parseSelectionIds($this->collection, $this->request);
        $conditionTreeIds = ConditionTreeFactory::matchIds($this->collection, $selectionIds['ids']);
        if ($selectionIds['areExcluded']) {
            $conditionTreeIds = $conditionTreeIds->inverse();
        }

        $filter = ContextFilterFactory::build(
            $this->collection,
            $this->request,
            ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    $conditionTreeIds,
                ]
            )
        );

        $this->collection->delete($this->caller, $filter, Arr::flatten($selectionIds['ids']));

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
