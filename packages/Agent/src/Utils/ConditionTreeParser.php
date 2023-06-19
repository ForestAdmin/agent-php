<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class ConditionTreeParser
{
    /**
     * @throws \Exception
     */
    public static function fromPLainObject(CollectionContract $collection, array $filters): ConditionTree
    {
        if (self::isLeaf($filters)) {
            $operator = ucwords($filters['operator'], '_');
            $value = self::parseValue($collection, $filters);

            return new ConditionTreeLeaf($filters['field'], $operator, $value);
        }

        if (self::isBranch($filters)) {
            $aggregator = ucfirst($filters['aggregator']);

            $conditions = [];
            foreach ($filters['conditions'] as $subTree) {
                $conditions[] = self::fromPLainObject($collection, $subTree);
            }

            return count($conditions) !== 1
                ? new ConditionTreeBranch($aggregator, $conditions)
                : $conditions[0];
        }

        throw new \Exception('Failed to instantiate condition tree');
    }

    private static function parseValue(CollectionContract $collection, array $leaf)
    {
        $schema = CollectionUtils::getFieldSchema($collection, $leaf['field']);

        if (ucfirst($leaf['operator']) === Operators::IN && is_string($leaf['value'])) {
            $values = collect(explode(',', $leaf['value']))
                ->map(fn ($item) => trim($item));

            if ($schema->getColumnType() === PrimitiveType::BOOLEAN) {
                // Cast values into bool
                return $values->map(fn ($item) => ! in_array(strtolower($item), ['false', '0', 'no']))
                    ->all();
            }

            if ($schema->getColumnType() === PrimitiveType::NUMBER) {
                return $values->map(fn ($item) => (float) $item)
                    ->filter(fn ($number) => ! is_nan($number) && is_finite($number))
                    ->all();
            }

            return $values->all();
        }

        return $leaf['value'];
    }

    private static function isLeaf(array $filters): bool
    {
        return array_key_exists('field', $filters) && array_key_exists('operator', $filters);
    }

    private static function isBranch(array $filters): bool
    {
        return array_key_exists('aggregator', $filters) && array_key_exists('conditions', $filters);
    }
}
