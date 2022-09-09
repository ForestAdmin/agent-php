<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\BodyParser;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema;

class DissociateRelated extends AbstractRelationRoute
{
    public function setupRoutes(): AbstractRoute
    {
        $this->addRoute(
            'forest.related.dissociate',
            'delete',
            '/{collectionName}/{id}/relationships/{relationName}',
            fn ($args) => $this->handleRequest($args)
        );

        return $this;
    }

    public function handleRequest(array $args = []): array
    {
        $this->build($args);
        $scope = $this->permissions->getScope($this->childCollection);

        $id = Id::unpackId($this->collection, $args['id']);
        $isDeleteMode = $this->request->get('delete') ?? false;
        $selectionIds = BodyParser::parseSelectionIds($this->childCollection, $this->request);
        $this->filter = $this->getBaseForeignFilter($scope);

        $relation = Schema::getToManyRelation($this->collection, $args['relationName']);

        if ($isDeleteMode) {
            if ($relation->getType() === 'ManyToMany') {
                $filter = FilterFactory::makeThroughFilter($this->collection, $id, $args['relationName'], $this->caller, $this->filter);
            } else {
                $filter = FilterFactory::makeForeignFilter($this->collection, $id, $args['relationName'], $this->caller, $this->filter);
            }

            $this->childCollection->deleteBulk($this->caller, $filter, $selectionIds['areExcluded'], $selectionIds['ids']);
        } else {
            [$pk] = Schema::getPrimaryKeys($this->collection);
            $parentValue = CollectionUtils::getValue($this->collection, $this->caller, $id, $pk);
            $childIds = Id::unpackIds($this->childCollection, collect($this->request->input('data'))->pluck('id')->toArray());
            $this->collection->dissociate($this->caller, $parentValue, $relation, $childIds);
        }

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    private function getBaseForeignFilter($scope)
    {
        //$selectionIds = BodyParser::parseSelectionIds($this->childCollection, $this->request);

        return ContextFilterFactory::build($this->childCollection, $this->request, $scope);

        // TODO NEED TO CHECK
//        return ContextFilterFactory.build(this.foreignCollection, context, null, {
//          conditionTree: ConditionTreeFactory.intersect(
//            await this.services.permissions.getScope(this.foreignCollection, context),
//            QueryStringParser.parseConditionTree(this.foreignCollection, context),
//            selectedIds,
//          ),
//        });
    }
}
