<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;

class AssociateRelated extends AbstractRelationRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.associate',
            'post',
            '/{collectionName}/{id}/relationships/{relationName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('edit:' . $this->collection->getName());

        $id = Id::unpackId($this->collection, $args['id']);
        $childId = Id::unpackId($this->childCollection, $this->request->input('data')[0]['id']);
        $relation = Schema::getToManyRelation($this->collection, $args['relationName']);

        [$pk] = Schema::getPrimaryKeys($this->collection);
        $parentValue = CollectionUtils::getValue($this->collection, $this->caller, $id, $pk);

        $parentFilter = ContextFilterFactory::build(
            $this->collection,
            $this->request,
            ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->collection),
                    new ConditionTreeLeaf($pk, 'Equal', $parentValue),
                ]
            )
        );

        [$pkChild] = Schema::getPrimaryKeys($this->childCollection);
        $childValue = CollectionUtils::getValue($this->childCollection, $this->caller, $childId, $pkChild);

        $childFilter = ContextFilterFactory::build(
            $this->childCollection,
            $this->request,
            ConditionTreeFactory::intersect(
                [
                    $this->permissions->getScope($this->childCollection),
                    new ConditionTreeLeaf($pkChild, 'Equal', $childValue),
                ]
            )
        );

        $this->collection->associate($this->caller, $parentFilter, $childFilter, $relation);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
