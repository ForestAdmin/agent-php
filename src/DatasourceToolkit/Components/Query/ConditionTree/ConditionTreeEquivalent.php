<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms\Comparisons;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms\Pattern;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms\Time;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class ConditionTreeEquivalent
{
    private static ?array $alternatives = null;

    public static function getEquivalentTree(ConditionTreeLeaf $leaf, array $operators, string $columnType, string $timezone)
    {
        return self::getReplacer($leaf->getOperator(), $operators, $columnType)($leaf, $timezone);
    }

    public static function hasEquivalentTree(string $operator, array $filterOperators, string $columnType): bool
    {
        return (bool) self::getReplacer($operator, $filterOperators, $columnType);
    }

    private static function getReplacer(string $operator, array $filterOperators, string $columnType, array $visited = [])
    {
        if (in_array($operator, $filterOperators, true)) {
            return static fn ($leaf) => $leaf;
        }

        foreach (self::getAlternatives($operator) ?? [] as $key => $alt) {
            $replacer = $alt['replacer'];
            $dependsOn = $alt['dependsOn'];
            $valid = ! array_key_exists('forTypes', $alt) || in_array($alt['forTypes'], PrimitiveType::tree(), true);

            if ($valid && ! array_key_exists($key, $visited)) {
                $dependsReplacer = collect($dependsOn)->map(fn ($replacement) => self::getReplacer($replacement, $filterOperators, $columnType, [...$visited, $alt]));

                if (collect($dependsReplacer)->every(fn ($r) => (bool) $r)) {
                    return static function ($leaf, $timezone) use ($replacer, $dependsReplacer, $operator) {
                        $replacer($leaf, $timezone)->replaceLeafs(
                            function ($subLeaf) use ($timezone, $dependsReplacer, $operator) {
                                $dependsReplacer[array_search($operator, $subLeaf, true)]($subLeaf, $timezone);
                            }
                        );
                    };
                }
            }
        }

        return null;
    }

    private static function getAlternatives(string $operator): ?array
    {
        // Init cache at first call to work around cyclic dependencies
        if (! self::$alternatives) {
            self::$alternatives = array_merge(
                Comparisons::equalityTransforms(),
                Pattern::patternTransforms(),
                Time::timeTransforms(),
            );
        }

        return self::$alternatives[$operator] ?? null;
    }
}
