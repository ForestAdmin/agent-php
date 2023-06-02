<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

final class Comparisons
{
    public static function equalityTransforms(): array
    {
        return [
            Operators::BLANK     => [
                [
                    'dependsOn' => [Operators::IN],
                    'forTypes'  => ['String'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::IN, value: [null, '']),
                ],
                [
                    'dependsOn' => [Operators::MISSING],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::MISSING),
                ],
            ],
            Operators::MISSING   => [
                [
                    'dependsOn' => [Operators::EQUAL],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::EQUAL, value: null),
                ],
            ],
            Operators::PRESENT   => [
                [
                    'dependsOn' => [Operators::NOT_IN],
                    'forTypes'  => ['String'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::NOT_IN, value: [null, '']),
                ],
                [
                    'dependsOn' => [Operators::NOT_EQUAL],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::NOT_EQUAL, value: null),
                ],
            ],
            Operators::EQUAL     => [
                [
                    'dependsOn' => [Operators::IN],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::IN, value: [$leaf->getValue()]),
                ],
            ],
            Operators::IN        => [
                [
                    'dependsOn' => [Operators::EQUAL, Operators::MATCH],
                    'forTypes'  => ['String'],
                    'replacer'  => function ($leaf) {
                        $values = $leaf->getValue();
                        $conditions = [];

                        foreach ([null, ''] as $value) {
                            if (in_array($value, $values, true)) {
                                $conditions[] = new ConditionTreeLeaf(field: $leaf->getField(), operator: Operators::EQUAL, value: $value);
                            }
                        }

                        if (collect($values)->some(fn ($value) => $value !== null && $value !== '')) {
                            $escaped = collect($values)
                                ->filter(fn ($value) => $value !== null && $value !== '')
                                ->toArray();

                            $conditions[] = new ConditionTreeLeaf(field: $leaf->getField(), operator: Operators::MATCH, value: "/" . implode('|', $escaped) . "/g");
                        }

                        return ConditionTreeFactory::union($conditions);
                    },
                ],
                [
                    'dependsOn' => [Operators::EQUAL],
                    'replacer'  => fn ($leaf) => ConditionTreeFactory::union(
                        collect($leaf->getValue())
                            ->map(fn ($item) => $leaf->override(operator: Operators::EQUAL, value: $item))
                            ->toArray()
                    ),
                ],
            ],
            Operators::NOT_EQUAL => [
                [
                    'dependsOn' => [Operators::NOT_IN],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: Operators::NOT_IN, value: [$leaf->getValue()]),
                ],
            ],
            Operators::NOT_IN    => [
                [
                    'dependsOn' => [Operators::NOT_EQUAL],
                    'replacer'  => function ($leaf) {
                        $trees = collect($leaf->getValue())
                            ->map(fn ($item) => $leaf->override(operator: Operators::NOT_EQUAL, value: $item))
                            ->toArray();

                        return ConditionTreeFactory::intersect($trees);
                    },
                ],
            ],
        ];
    }
}
