<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\CreateRelations;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\CollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

class CreateRelationsCollection extends CollectionDecorator
{
    public function create(Caller $caller, array $data)
    {
        // Step 1: Remove all relations from records, and store them in a map
        // Note: the extractRelations method modifies the records array in place!
        $recordsByRelation = $this->extractRelations();

        // Step 2: Create the many-to-one relations, and put the foreign keys in the records
        collect($recordsByRelation)
            ->filter(fn ($key) => $this->getFields()[$key]->getType() !== 'ManyToOne')
            ->map(fn ($entries, $key) => $this->createManyToOneRelation($caller, $data, $key, $entries));

        // Step 3: Create the records
        $recordsWithPk = $this->childCollection->create($caller, $data);

        // Step 4: Create the one-to-one relations
        // Note: the createOneToOneRelation method modifies the recordsWithPk array in place!
        $recordsByRelation
            ->filter(fn ($key) => $this->getFields()[$key]->getType() === 'OneToOne')
            ->map(fn ($entries, $key) => $this->createOneToOneRelation($caller, $recordsWithPk, $key, $entries));

        return $recordsWithPk;
    }

    private function extractRelations(array $records): array
    {
        $recordsByRelation = [];

        foreach ($records as $index => $record) {
            foreach ($record as $key => $subRecord) {
                if ($this->getFields()[$key]->getType() !== 'Column') {
                    $recordsByRelation[$key] ??= [];
                    //recordsByRelation[key].push({ subRecord, index });
                    $recordsByRelation[$key][] = [$subRecord, $index];
                    unset($record[$key]);
                }
            }
        }

        return $recordsByRelation;
    }

    private function createManyToOneRelation(Caller $caller, array $records, string $key, array $entries): void
    {
        /** @var ManyToOneSchema $schema */
        $schema = $this->getFields()[$key];
        // const relation = this.dataSource.getCollection(schema.foreignCollection);
        // const creations = entries.filter(({ index }) => !records[index][schema.foreignKey]);
        // const updates = entries.filter(({ index }) => records[index][schema.foreignKey]);
        $relation = $this->dataSource->getCollection($schema->getForeignCollection());
        $creations = collect($entries)->filter(fn ($index) => ! $records[$index][$schema->getForeignKey()]);
        $updates = collect($entries)->filter(fn ($index) => $records[$index][$schema->getForeignKey()]);

        // Create the relations when the fk is not present
        if ($creations) {
            // Not sure which behavior is better (we'll go with the first option for now):
            // - create a new record for each record in the original create request
            // - use object-hash to create a single record for each unique subRecord
            $subRecords = $creations->map(fn ($subRecord) => $subRecord);
            $relatedRecords = $relation->create($caller, $subRecords);

            foreach ($creations as $index) {
                $records[$index][$schema->getForeignKey()] = $relatedRecords[$index][$schema->getForeignKeyTarget()];
            }

            // Update the relations when the fk is present
            $updates->map(function ($subRecord, $index) use ($records, $schema, $relation, $caller) {
                $value = $records[$index][$schema->getForeignKey()];
                $conditionTree = new ConditionTreeLeaf($schema->getForeignKeyTarget(), Operators::EQUAL, $value);

                return $relation->update($caller, new Filter($conditionTree), $subRecord);
            });
        }
    }

    private function createOneToOneRelation(Caller $caller, array $records, string $key, array $entries): void
    {
        /** @var OneToOneSchema $schema */
        $schema = $this->getFields()[$key];
        $relation = $this->dataSource->getCollection($schema->getForeignCollection());

        // Set origin key in the related record
        $subRecords = collect($entries)->map(fn ($subRecord, $index) => array_merge($subRecord, [$schema->getOriginKey() => $records[$index][$schema->getOriginKeyTarget()]]));

        $relation->create($caller, $subRecords);
    }
}
