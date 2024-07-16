<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class UpdateRelated extends AbstractRelationRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.update',
            'put',
            '/{collectionName}/{id}/relationships/{relationName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $this->permissions->can('edit', $this->collection);

        $id = Id::unpackId($this->collection, $args['id']);
        $childId = null !== $this->request->input('data.id') ? Id::unpackId($this->childCollection, $this->request->input('data.id')) : null;
        $relation = $this->collection->getFields()[$args['relationName']];

        if ($relation->getType() === 'ManyToOne') {
            $this->updateManyToOne($relation, $id, $childId);
        } elseif ($relation->getType() === 'OneToOne') {
            $this->updateOneToOne($relation, $id, $childId);
        } elseif ($relation->getType() === 'PolymorphicManyToOne') {
            $this->updatePolymorphicManyToOne($relation, $id, $childId);
        } elseif ($relation->getType() === 'PolymorphicOneToOne') {
            $this->updatePolymorphicOneToOne($relation, $id, $childId);
        }

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    private function updateManyToOne(ManyToOneSchema $relation, $parentId, $childId): void
    {
        $scope = $this->permissions->getScope($this->childCollection);
        $foreignValue = $childId ? CollectionUtils::getValue($this->childCollection, $this->caller, $childId, $relation->getForeignKeyTarget()) : null;

        // Overwrite old foreign key with new one (only one query needed).
        $fkOwner = ConditionTreeFactory::matchIds($this->collection, [$parentId]);

        $this->collection->update(
            $this->caller,
            new Filter(
                conditionTree: ConditionTreeFactory::intersect([$fkOwner, $scope]),
            ),
            [$relation->getForeignKey() => $foreignValue]
        );
    }

    private function updatePolymorphicManyToOne(PolymorphicManyToOneSchema $relation, $parentId, $childId): void
    {
        $polymorphicType = array_filter($relation->getForeignCollections(), fn ($name) => str_ends_with($name, $this->childCollection->getName()))[0];
        $foreignValue = CollectionUtils::getValue(
            $this->childCollection,
            $this->caller,
            $childId,
            $relation->getForeignKeyTargets()[$polymorphicType]
        );

        $this->collection->update(
            $this->caller,
            new Filter(
                conditionTree: ConditionTreeFactory::matchIds($this->collection, [$parentId]),
            ),
            [
                $relation->getForeignKey()          => $foreignValue,
                $relation->getForeignKeyTypeField() => $polymorphicType,
            ]
        );
    }

    private function updateOneToOne(OneToOneSchema $relation, $parentId, $childId): void
    {
        $scope = $this->permissions->getScope($this->childCollection);
        $originValue = CollectionUtils::getValue($this->collection, $this->caller, $parentId, $relation->getOriginKeyTarget());

        $this->breakOldOneToOneRelationship($scope, $relation, $originValue, $childId);
        $this->createNewOneToOneRelationship($scope, $relation, $originValue, $childId);
    }

    private function updatePolymorphicOnetoOne(PolymorphicOneToOneSchema $relation, $parentId, $childId): void
    {
        $scope = $this->permissions->getScope($this->childCollection);
        $originValue = CollectionUtils::getValue($this->collection, $this->caller, $parentId, $relation->getOriginKeyTarget());

        $this->breakOldPolymorphicOneToOneRelationship($scope, $relation, $originValue, $childId);
        $this->createNewPolymorphicOneToOneRelationship($scope, $relation, $originValue, $childId);
    }

    private function breakOldOneToOneRelationship($scope, $relation, $originValue, $childId)
    {
        $oldFkOwnerFilter = new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    $scope,
                    new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue),
                    // Don't set the new record's field to null
                    // if it's already initialized with the right value
                    $childId ? ConditionTreeFactory::matchIds($this->childCollection, [$childId])->inverse() : [],
                ]
            )
        );

        $result = $this->childCollection->aggregate(
            $this->caller,
            $oldFkOwnerFilter,
            new Aggregation(operation: 'Count'),
            1
        );

        if ($result[0]['value'] > 0) {
            $this->childCollection->update($this->caller, $oldFkOwnerFilter, [$relation->getOriginKey() => null]);
        }
    }

    private function breakOldPolymorphicOneToOneRelationship($scope, $relation, $originValue, $childId)
    {
        $oldFkOwnerFilter = new Filter(
            conditionTree: ConditionTreeFactory::intersect(
                [
                    $scope,
                    new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue),
                    new ConditionTreeLeaf($relation->getOriginTypeField(), 'Equal', $relation->getOriginTypeValue()),
                    // Don't set the new record's field to null
                    // if it's already initialized with the right value
                    $childId ? ConditionTreeFactory::matchIds($this->childCollection, [$childId])->inverse() : [],
                ]
            )
        );

        $result = $this->childCollection->aggregate(
            $this->caller,
            $oldFkOwnerFilter,
            new Aggregation(operation: 'Count'),
            1
        );

        // add a behavior if originKey & originTypeField cannot be null

        if ($result[0]['value'] > 0) {
            $this->childCollection->update(
                $this->caller,
                $oldFkOwnerFilter,
                [
                    $relation->getOriginKey()       => null,
                    $relation->getOriginTypeField() => null,
                ]
            );
        }
    }

    private function createNewOneToOneRelationship($scope, $relation, $originValue, $childId)
    {
        if ($childId) {
            $this->childCollection->update(
                $this->caller,
                new Filter(
                    conditionTree: ConditionTreeFactory::intersect(
                        [
                            $scope,
                            ConditionTreeFactory::matchIds($this->childCollection, [$childId]),
                        ]
                    )
                ),
                [$relation->getOriginKey() => $originValue]
            );
        }
    }

    private function createNewPolymorphicOneToOneRelationship($scope, $relation, $originValue, $childId)
    {
        if ($childId) {
            $this->childCollection->update(
                $this->caller,
                new Filter(
                    conditionTree: ConditionTreeFactory::intersect(
                        [
                            $scope,
                            ConditionTreeFactory::matchIds($this->childCollection, [$childId]),
                        ]
                    )
                ),
                [
                    $relation->getOriginKey()       => $originValue,
                    $relation->getOriginTypeField() => $relation->getOriginTypeValue(),
                ]
            );
        }
    }
}
