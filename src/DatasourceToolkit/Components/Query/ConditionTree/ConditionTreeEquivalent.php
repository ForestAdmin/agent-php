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
        $replacer = self::getReplacer($leaf->getOperator(), $operators, $columnType);

        return $replacer ? $replacer($leaf, $timezone) : null;
    }

    public static function hasEquivalentTree(string $operator, array $filterOperators, string $columnType): bool
    {
        if (in_array($operator, $filterOperators, true)) {
            return true;
        }

        return (bool) self::getReplacer($operator, $filterOperators, $columnType);
    }

    private static function getReplacer(string $operator, array $filterOperators, string $columnType, array $visited = [])
    {
        if (in_array($operator, $filterOperators, true)) {
            return static fn ($leaf) => $leaf;
        }

        foreach (self::getAlternatives($operator) ?? [] as $alt) {
            $replacer = $alt['replacer'];
            $dependsOn = $alt['dependsOn'];
            $valid = ! array_key_exists('forTypes', $alt) || in_array($alt['forTypes'], PrimitiveType::tree(), true);

            if ($valid && ! in_array($alt, $visited, true)) {
                $dependsReplacer = collect($dependsOn)
                    ->mapWithKeys(
                        function ($replacement) use ($filterOperators, $columnType, $visited, $alt) {
                            return [$replacement => self::getReplacer($replacement, $filterOperators, $columnType, [...$visited, $alt])];
                        }
                    );

                if (collect($dependsReplacer)->every(fn ($r) => (bool) $r)) {
                    return static function ($leaf, $timezone) use ($replacer, $dependsReplacer) {
                        /** @var ConditionTree $conditionTree */
                        $conditionTree = $replacer($leaf, $timezone);

                        return $conditionTree->replaceLeafs(
                            function (ConditionTreeLeaf $subLeaf) use ($timezone, $dependsReplacer) {
                                $closure = $dependsReplacer[$subLeaf->getOperator()];

                                return $closure($subLeaf, $timezone);
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
