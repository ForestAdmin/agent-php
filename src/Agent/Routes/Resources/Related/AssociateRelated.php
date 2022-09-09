<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
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
        $scope = $this->permissions->getScope($this->childCollection);
        // todo is filter useful here
        // $this->filter = ContextFilterFactory::build($this->childCollection, $this->request, $scope);

        $id = Id::unpackId($this->collection, $args['id']);
        $childId = Id::unpackId($this->childCollection, $this->request->input('data')[0]['id']);
        $relation = Schema::getToManyRelation($this->collection, $args['relationName']);

        [$pkChild] = Schema::getPrimaryKeys($this->childCollection);
        $childValue = CollectionUtils::getValue($this->childCollection, $this->caller, $childId, $pkChild);

        [$pk] = Schema::getPrimaryKeys($this->collection);
        $parentValue = CollectionUtils::getValue($this->collection, $this->caller, $id, $pk);

        $this->collection->associate($this->caller, $parentValue, $relation, $childValue);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
