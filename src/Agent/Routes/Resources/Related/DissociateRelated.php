<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Resources\Related;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRelationRoute;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\FilterFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection;
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
        $this->permissions->can('delete', $this->collection);

        $parentId = Id::unpackId($this->collection, $args['id']);
        $isDeleteMode = $this->request->get('delete') ?? false;
        $selectionIds = Id::parseSelectionIds($this->childCollection, $this->request);
        $childFilter = $this->getBaseForeignFilter($selectionIds);

        $relation = Schema::getToManyRelation($this->collection, $args['relationName']);

        if ($relation instanceof OneToManySchema) {
            $this->dissociateOrDeleteOneToMany($relation, $args['relationName'], $parentId, $isDeleteMode, $childFilter);
        } else {
            $this->dissociateOrDeleteManyToMany($relation, $args['relationName'], $parentId, $isDeleteMode, $childFilter);
        }

        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    private function dissociateOrDeleteOneToMany(OneToManySchema $relation, string $relationName, array $parentId, bool $isDeleteMode, Filter $filter): void
    {
        $foreignFilter = $this->makeForeignFilter($parentId, $filter, $relationName);

        if ($isDeleteMode) {
            $this->childCollection->delete($this->caller, $foreignFilter);
        } else {
            $this->childCollection->update($this->caller, $foreignFilter, ['attributes' => [$relation->getOriginKey() => null]]);
        }
    }

    private function dissociateOrDeleteManyToMany(ManyToManySchema $relation, string $relationName, array $parentId, bool $isDeleteMode, Filter $filter): void
    {
        $throughCollection = $this->datasource->getCollection($relation->getThroughCollection());

        if ($isDeleteMode) {
            // Generate filters _BEFORE_ deleting stuff, otherwise things break.
            $throughFilter = $this->makeThroughFilter($parentId, $filter, $relationName);
            $foreignFilter = $this->makeForeignFilter($parentId, $filter, $relationName);
            // Delete records from through collection
            $throughCollection->delete($this->caller, $throughFilter);

            // Let the datasource crash when:
            // - the records in the foreignCollection are linked to other records in the origin collection
            // - the underlying database/api is not cascading deletes
            $this->childCollection->delete($this->caller, $foreignFilter);
        } else {
            // Only delete records from through collection
            $throughFilter = $this->makeThroughFilter($parentId, $filter, $relationName);
            $throughCollection->delete($this->caller, $throughFilter);
        }
    }

    protected function makeForeignFilter(array $parentId, Filter $baseForeignFilter, string $relationName): Filter
    {
        return FilterFactory::makeForeignFilter($this->collection, $parentId, $relationName, $this->caller, $baseForeignFilter);
    }

    protected function makeThroughFilter(array $parentId, Filter $baseForeignFilter, string $relationName): Filter
    {
        return FilterFactory::makeThroughFilter($this->collection, $parentId, $relationName, $this->caller, $baseForeignFilter);
    }

    /**
     * @param array $selectionIds
     * @return Filter
     */
    private function getBaseForeignFilter(array $selectionIds): Filter
    {
        if (! array_key_exists('ids', $selectionIds) || empty($selectionIds['ids'])) {
            throw new ForestException('Expected no empty id list');
        }

        $conditionTreeIds = ConditionTreeFactory::matchIds($this->childCollection, $selectionIds['ids']);
        if ($selectionIds['areExcluded']) {
            $conditionTreeIds->inverse();
        }

        $conditionTree = ConditionTreeFactory::intersect(
            [
                $this->permissions->getScope($this->childCollection),
                QueryStringParser::parseConditionTree($this->childCollection, $this->request),
                $conditionTreeIds,
            ]
        );

        return ContextFilterFactory::build($this->childCollection, $this->request, $conditionTree);
    }
}
