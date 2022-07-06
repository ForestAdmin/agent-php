<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTree;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

class ConditionTreeParser
{
    public static function fromPLainObject(Collection $collection, array $filters): ConditionTree
    {
        if (self::isLeaf($filters)) {
            $operator = ucwords($filters['operator'], '_');
            $value = self::parseValue($collection, $filters);
        }
    }

    private static function parseValue(Collection $collection, array $leaf)
    {
        $schema = CollectionUtils::getFieldSchema($collection, $leaf['field']);

        if ($leaf['operator'] === 'In' && is_string($leaf['value'])) {
            $values = collect(explode(',', $leaf['value']))
                ->each(fn($item) => trim($item));

            if ($schema->getColumnType() === PrimitiveType::Boolean()) {
                /* return leaf.value
                    .split(',')
                    .map(bool => !['false', '0', 'no'].includes(bool.toLowerCase().trim())); */
                // todo
            }

            if ($schema->getColumnType() === PrimitiveType::Number()) {
                return $values->each(fn($item) => (float) $item)
                    ->filter(fn($number) => ! is_nan($number) && is_finite($number))
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
