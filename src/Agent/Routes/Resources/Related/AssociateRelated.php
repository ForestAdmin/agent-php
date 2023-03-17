<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

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

        $parentId = Id::unpackId($this->collection, $args['id']);
        $targetedRelationId = Id::unpackId($this->childCollection, $this->request->input('data')[0]['id']);
        $this->permissions->getScope($this->collection);
        $relation = SchemaUtils::getToManyRelation($this->collection, $args['relationName']);

        if ($relation instanceof OneToManySchema) {
            $this->associateOneToMany($relation, $parentId, $targetedRelationId);
        } else {
            $this->associateManyToMany($relation, $parentId, $targetedRelationId);
        }

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    private function associateOneToMany(OneToManySchema $relation, array $parentId, array $targetedRelationId): void
    {
        $id = SchemaUtils::getPrimaryKeys($this->childCollection)[0];
        $value = CollectionUtils::getValue($this->childCollection, $this->caller, $targetedRelationId, $id);
        $filter = ContextFilterFactory::build(
            $this->collection,
            $this->request,
            ConditionTreeFactory::intersect(
                [
                    new ConditionTreeLeaf('id', Operators::EQUAL, $value),
                    $this->permissions->getScope($this->collection),
                ]
            )
        );
        $value = CollectionUtils::getValue($this->collection, $this->caller, $parentId, $relation->getOriginKeyTarget());
        $this->childCollection->update($this->caller, $filter, [$relation->getOriginKey() => $value]);
    }

    private function associateManyToMany(ManyToManySchema $relation, array $parentId, array $targetedRelationId)
    {
        $id = SchemaUtils::getPrimaryKeys($this->childCollection)[0];
        $foreign = CollectionUtils::getValue($this->childCollection, $this->caller, $targetedRelationId, $id);
        $id = SchemaUtils::getPrimaryKeys($this->collection)[0];
        $origin = CollectionUtils::getValue($this->collection, $this->caller, $parentId, $id);
        $record = [
            $relation->getOriginKey()  => $origin,
            $relation->getForeignKey() => $foreign,
        ];

        $throughCollection = $this->datasource->getCollection($relation->getThroughCollection());
        $throughCollection->create($this->caller, $record);
    }
}
