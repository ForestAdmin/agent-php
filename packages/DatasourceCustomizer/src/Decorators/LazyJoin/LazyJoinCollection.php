<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\LazyJoin;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

class LazyJoinCollection extends CollectionDecorator
{
    public function list(Caller $caller, PaginatedFilter $filter, Projection $projection): array
    {
        $simplifiedProjection = $this->getProjectionWithoutUselessJoins($projection);
        $refinedFilter = $this->refineFilter($caller, $filter);

        $records = $this->childCollection->list($caller, $refinedFilter, $simplifiedProjection);

        return $this->applyJoinsOnRecords($projection, $simplifiedProjection, $records);
    }

    public function aggregate(Caller $caller, Filter $filter, Aggregation $aggregation, ?int $limit = null)
    {
        $refinedFilter = $this->refineFilter($caller, $filter);
        $replaced = [];
        $refinedAggregation = $aggregation->replaceFields(
            function ($fieldName) use ($aggregation, &$replaced) {
                if ($this->isUselessJoin(explode(':', $fieldName)[0], $aggregation->getProjection())) {
                    $newFieldName = $this->getForeignKeyForProjection($fieldName);
                    $replaced[$newFieldName] = $fieldName;

                    return $newFieldName;
                }

                return $fieldName;
            }
        );

        $results = $this->childCollection->aggregate($caller, $refinedFilter, $refinedAggregation, $limit);

        return $this->applyJoinsOnAggregateResult($aggregation, $refinedAggregation, $results, $replaced);
    }

    protected function refineFilter(?Caller $caller, Filter|PaginatedFilter|null $filter): Filter|PaginatedFilter|null
    {
        return $filter?->override(
            conditionTree: $filter->getConditionTree()?->replaceLeafs(
                function (ConditionTreeLeaf $leaf) use ($filter) {
                    if (
                        $this->isUselessJoin(
                            explode(':', $leaf->getField())[0],
                            $filter->getConditionTree()->getProjection()
                        )
                    ) {
                        return $leaf->override(field: $this->getForeignKeyForProjection($leaf->getField()));
                    }

                    return $leaf;
                }
            )
        );
    }

    private function getForeignKeyForProjection(String $fieldName): string
    {
        $relationName = explode(':', $fieldName)[0];
        $relationSchema = $this->getSchema()->get($relationName);

        return $relationSchema->getForeignKey();
    }

    private function isUselessJoin(String $relationName, Projection $projection): bool
    {
        $relationSchema = $this->getSchema()->get($relationName);
        $subProjection = $projection->relations()[$relationName] ?? null;

        return $relationSchema instanceof ManyToOneSchema
            && count($subProjection) === 1
            && $subProjection[0] === $relationSchema->getForeignKeyTarget();
    }

    private function getProjectionWithoutUselessJoins(Projection $projection): Projection
    {
        $newProjectionArray = $projection->all();

        foreach ($projection->relations() as $relationName => $relationProjection) {
            if ($this->isUselessJoin($relationName, $projection)) {
                // remove foreign key target from projection
                $newProjectionArray = array_values(array_filter(
                    $newProjectionArray,
                    static fn ($value, $key) => $value !== "$relationName:$relationProjection[0]",
                    ARRAY_FILTER_USE_BOTH
                ));

                // add foreign keys to projection
                $newProjectionArray[] = $this->getForeignKeyForProjection("$relationName:$relationProjection[0]");
            }
        }

        return new Projection($newProjectionArray);
    }

    private function applyJoinsOnRecords(Projection $initialProjection, Projection $requestedProjection, array $records)
    {
        if ($initialProjection !== $requestedProjection) {
            $projectionToAdd = new Projection(
                $initialProjection->reject(fn ($field) => $requestedProjection->contains($field))
            );
            $projectionToRemove = new Projection(
                $requestedProjection->reject(fn ($field) => $initialProjection->contains($field))
            );

            foreach ($records as &$record) {
                // add to record relation:id
                foreach ($projectionToAdd->relations() as $relationName => $relationProjection) {
                    $relationSchema = $this->getSchema()->get($relationName);
                    if ($relationSchema instanceof ManyToOneSchema) {
                        $fkValue = $record[$this->getForeignKeyForProjection("$relationName:$relationProjection[0]")];
                        $record[$relationName] = isset($fkValue) ? [$relationProjection[0] => $fkValue] : null;
                    }
                }

                // remove foreign keys
                foreach ($projectionToRemove as $fieldName) {
                    unset($record[$fieldName]);
                }
            }
        }

        return $records;
    }

    private function applyJoinsOnAggregateResult(
        Aggregation $initialAggregation,
        Aggregation $requestedAggregation,
        array $results,
        array $fieldsToReplace
    ) {
        if ($initialAggregation !== $requestedAggregation) {
            foreach ($results as &$result) {
                foreach ($result['group'] as $field => $value) {
                    if(isset($fieldsToReplace[$field])) {
                        $result['group'][$fieldsToReplace[$field]] = $value;
                        unset($result['group'][$field]);
                    }
                }
            }
        }

        return $results;
    }
}
