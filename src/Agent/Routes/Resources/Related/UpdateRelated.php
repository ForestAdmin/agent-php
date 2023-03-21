<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
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
        $this->permissions->can('edit:' . $this->collection->getName());

        $id = Id::unpackId($this->collection, $args['id']);
        $childId = null !== $this->request->input('data.id') ? Id::unpackId($this->childCollection, $this->request->input('data.id')) : null;
        $relation = $this->collection->getFields()[$args['relationName']];

        if ($relation->getType() === 'ManyToOne') {
            $this->updateManyToOne($relation, $id, $childId);
        } elseif ($relation->getType() === 'OneToOne') {
            $this->updateOneToOne($relation, $id);
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

    private function updateOneToOne(OneToOneSchema $relation, $parentId): void
    {
        $scope = $this->permissions->getScope($this->childCollection);
        $originValue = CollectionUtils::getValue($this->collection, $this->caller, $parentId, $relation->getOriginKeyTarget());
        // Break old relation (may update zero or one records).
        $oldFkOwner = new ConditionTreeLeaf($relation->getOriginKey(), 'Equal', $originValue);

        $this->childCollection->update(
            $this->caller,
            new Filter(
                conditionTree: ConditionTreeFactory::intersect([$oldFkOwner, $scope]),
            ),
            [$relation->getOriginKey() => $originValue]
        );
    }
}
