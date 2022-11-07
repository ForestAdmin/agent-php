<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;
use Illuminate\Support\Collection as IlluminateCollection;

class ConditionTreeFactory
{
    public static function matchRecords(CollectionContract $collection, array $records): ConditionTree
    {
        $ids = collect($records)
            ->map(fn ($record) => RecordUtils::getPrimaryKeys($collection, $record))
            ->toArray();

        return self::matchIds($collection, $ids);
    }

    public static function matchIds(CollectionContract $collection, array $ids): ConditionTree
    {
        $primaryKeyNames = SchemaUtils::getPrimaryKeys($collection);

        if (count($primaryKeyNames) === 0) {
            throw new ForestException('Collection must have at least one primary key');
        }

        foreach ($primaryKeyNames as $name) {
            $operators = $collection->getFields()[$name]->getFilterOperators();

            if (! in_array(Operators::EQUAL, $operators, true) || ! in_array(Operators::IN, $operators, true)) {
                throw new ForestException("Field '$name' must support operators: ['Equal', 'In']");
            }
        }

        return self::matchFields($primaryKeyNames, $ids);
    }

    public static function intersect(array $trees): ?ConditionTree
    {
        $result = self::group('And', $trees);

        $isEmptyAnd = $result instanceof ConditionTreeBranch &&
            $result->getAggregator() === 'And' &&
            count($result->getConditions()) === 0;

        return $isEmptyAnd ? null : $result;
    }

    public static function union(array $trees): ConditionTree
    {
        return self::group('Or', $trees);
    }

    public static function fromArray(array $tree): ConditionTree
    {
        if (self::isLeaf($tree)) {
            return new ConditionTreeLeaf($tree['field'], ucwords($tree['operator'], '_'), $tree['value']);
        }

        if (self::isBranch($tree)) {
            $conditions = [];
            foreach ($tree['conditions'] as $condition) {
                $conditions[] = self::fromArray($condition);
            }
            $branch = new ConditionTreeBranch(ucfirst($tree['aggregator']), $conditions);

            return count($branch->getConditions()) === 1 ? $branch->getConditions()[0] : $branch;
        }

        throw new ForestException('Failed to instantiate condition tree from array');
    }

    private static function group(string $aggregator, array $trees): ConditionTree
    {
        $conditions = collect($trees)
            ->filter()
            ->reduce(
                static function ($currentConditions, $tree) use ($aggregator) {
                    return $tree instanceof ConditionTreeBranch && $tree->getAggregator() === $aggregator
                        ? [...$currentConditions, ...$tree->getConditions()]
                        : [...$currentConditions, $tree];
                },
                []
            );

        return count($conditions) === 1 ? $conditions[0] : new ConditionTreeBranch($aggregator, $conditions);
    }

    private static function matchFields(array $fields, array $values): ConditionTree
    {
        if (count($values) === 0) {
            return new ConditionTreeBranch('Or', []);
        }

        if (count($fields) === 1) {
            $fieldValues = [];
            foreach ($values as $value) {
                $fieldValues[] = $value[0];
            }

            return count($fieldValues) > 1
                ? new ConditionTreeLeaf($fields[0], 'In', $fieldValues)
                : new ConditionTreeLeaf($fields[0], 'Equal', $fieldValues[0]);
        }

        $firstField = $fields[0];
        unset($fields[0]);
        $otherFields = array_values($fields);

        $group = new IlluminateCollection();
        foreach ($values as $value) {
            $firstValue = $value[0];
            unset($value[0]);
            $otherValues = array_values($value);
            if ($group->has($firstValue)) {
                $group->get($firstValue)->push($otherValues);
            } else {
                $group->put($firstValue, collect([$otherValues]));
            }
        }

        return self::union(
            $group->map(
                fn ($subValues, $firstValue) => self::intersect(
                    [
                        self::matchFields([$firstField], [[$firstValue]]),
                        self::matchFields($otherFields, $subValues->toArray()),
                    ]
                )
            )->all(),
        );
    }

    private static function isLeaf(array $tree): bool
    {
        return array_key_exists('field', $tree)
            && array_key_exists('operator', $tree)
            && array_key_exists('value', $tree);
    }

    private static function isBranch(array $tree): bool
    {
        return array_key_exists('aggregator', $tree) && array_key_exists('conditions', $tree);
    }
}
