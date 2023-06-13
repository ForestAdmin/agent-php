<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\UpdateRelations;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\RelationSchema;

class UpdateRelationsCollection extends CollectionDecorator
{
    public function update(Caller $caller, Filter $filter, array $patch)
    {
        // Step 1: Perform the normal update
        if (collect(array_keys($patch))->contains(fn ($key) => $this->getFields()[$key]->getType() === 'Column')) {
            $patchWithoutRelations = collect($patch)->reduce(
                fn ($carry, $value, $key) => $this->getFields()[$key]->getType() === 'Column' ? array_merge($carry, [$key => $patch[$key]]) : $carry,
                []
            );

            $this->childCollection->update($caller, $filter, $patchWithoutRelations);
        }

        // Step 2: Perform additional updates for relations
        if (collect(array_keys($patch))->contains(fn ($key) => $this->getFields()[$key]->getType() !== 'Column')) {
            // Fetch the records that will be updated, to know which relations need to be created/updated
            $projection = $this->buildProjection($patch);
            $records = $this->childCollection->list($caller, new PaginatedFilter($filter->getConditionTree(), $filter->getSearch(), $filter->getSearchExtended(), $filter->getSegment()), $projection);

            // Perform the updates for each relation
            collect(array_keys($patch))
                ->filter(fn ($key) => $this->getFields()[$key]->getType() !== 'Column')
                ->map(fn ($key) => $this->createOrUpdateRelation($caller, $records, $key, $patch[$key]));
        }
    }

    /**
     * Build a projection that has enough information to know
     * - which relations need to be created/updated
     * - the values that will be used to build filters to target records
     * - the values that will be used to create/update the relations
     */
    private function buildProjection(array $patch): Projection
    {
        $projection = (new Projection())->withPks($this);

        foreach (array_keys($patch) as $key) {
            $schema = $this->getFields()[$key];

            if ($schema->getType() !== 'Column') {
                $relation = $this->dataSource->getCollection($schema->getForeignCollection());

                $projection = $projection->union((new Projection())->withPks($relation)->nest($key));
                if ($schema->getType() == 'ManyToOne') {
                    $projection = $projection->union((new Projection($schema->getForeignKeyTarget()))->nest($key));
                }
                if ($schema->getType() == 'OneToOne') {
                    $projection = $projection->union((new Projection($schema->getOriginKeyTarget()))->nest($key));
                }
            }
        }

        return $projection;
    }

    /**
     * Create or update the relation provided in the key parameter according to the patch.
     */
    private function createOrUpdateRelation(Caller $caller, array $records, string $key, array $patch)
    {
        /** @var RelationSchema $schema */
        $schema = $this->getFields()[$key];
        $relation = $this->dataSource->getCollection($schema->getForeignCollection());

        $creates = collect($records)->filter(fn ($r) => ! array_key_exists($key, $r) || $r[$key] === null);
        $updates = collect($records)->filter(fn ($r) => array_key_exists($key, $r) && $r[$key] !== null);

        if ($creates->isNotEmpty()) {
            if ($schema->getType() === 'ManyToOne') {
                // Create many-to-one relations
                $subRecord = $relation->create($caller, $patch);

                // Set foreign key on the parent records
                $conditionTree = ConditionTreeFactory::matchRecords($this, $creates->all());
                $parentPatch = [$schema->getForeignKey() => $subRecord[$schema->getForeignKeyTarget()]];

                $this->update($caller, new Filter($conditionTree), $parentPatch);
            } else {
                // Create the one-to-one relations that don't already exist
                $relation->create(
                    $caller,
                    $creates->map(
                        fn ($record) => array_merge($patch, [$schema->getOriginKey() => $record[$schema->getOriginKeyTarget()]])
                    )->toArray()
                );
            }
        }

        // Update the relations that already exist
        if ($updates->isNotEmpty()) {
            $subRecords = $updates->map(fn ($record) => $record[$key]);
            $conditionTree = ConditionTreeFactory::matchRecords($relation, $subRecords->toArray());

            $relation->update($caller, new Filter($conditionTree), $patch);
        }
    }
}
