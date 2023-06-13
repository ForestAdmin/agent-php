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
        $recordsByRelation = $this->extractRelations($data);

        // Step 2: Create the many-to-one relations, and put the foreign keys in the records
        collect($recordsByRelation)
            ->filter(fn ($value, $key) => $this->getFields()[$key]->getType() === 'ManyToOne')
            ->map(function ($entries, $key) use ($caller, &$data) {
                return $this->createManyToOneRelation($caller, $data, $key, $entries);
            });

        // Step 3: Create the records
        $recordsWithPk = $this->childCollection->create($caller, $data);

        // Step 4: Create the one-to-one relations
        // Note: the createOneToOneRelation method modifies the recordsWithPk array in place!
        collect($recordsByRelation)
            ->filter(fn ($value, $key) => $this->getFields()[$key]->getType() === 'OneToOne')
            ->map(fn ($entries, $key) => $this->createOneToOneRelation($caller, $recordsWithPk, $key, $entries));

        return $recordsWithPk;
    }

    private function extractRelations(array &$records): array
    {
        $recordsByRelation = [];

        foreach ($records as $index => $record) {
            if ($this->getFields()[$index]->getType() !== 'Column') {
                foreach ($record as $key => $subRecord) {
                    $recordsByRelation[$index] ??= [];
                    $recordsByRelation[$index][$key] = $subRecord;
                }
                unset($records[$index]);
            }
        }

        return $recordsByRelation;
    }

    private function createManyToOneRelation(Caller $caller, array &$records, string $key, array $entries): void
    {
        /** @var ManyToOneSchema $schema */
        $schema = $this->getFields()[$key];
        $relation = $this->dataSource->getCollection($schema->getForeignCollection());

        if (! in_array($schema->getForeignKey(), array_keys($records), true)) {
            $relatedRecord = $relation->childCollection->create($caller, $entries);
            $records[$schema->getForeignKey()] = $relatedRecord[$schema->getForeignKeyTarget()];
        } else {
            $value = $records[$schema->getForeignKey()];
            $conditionTree = new ConditionTreeLeaf($schema->getForeignKeyTarget(), Operators::EQUAL, $value);
            $relation->childCollection->update($caller, new Filter($conditionTree), $entries);
        }
    }

    private function createOneToOneRelation(Caller $caller, array $records, string $key, array $entries): void
    {
        /** @var OneToOneSchema $schema */
        $schema = $this->getFields()[$key];
        $relation = $this->dataSource->getCollection($schema->getForeignCollection());
        $relation->childCollection->create($caller, array_merge($entries, [$schema->getOriginKeyTarget() => $records[$schema->getOriginKey()]]));
    }
}
