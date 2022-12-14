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
                    'dependsOn' => [Operators::EQUAL],
                    'replacer'  => function ($leaf) {
                        $trees = collect($leaf->getValue())
                            ->map(fn ($item) => new ConditionTreeLeaf(field: $leaf->getField(), operator: Operators::EQUAL, value: $item))
                            ->toArray();

                        return ConditionTreeFactory::union($trees);
                    },
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
                        $trees = collect($leaf->getValue)
                            ->map(fn ($item) => $leaf->override(operator: Operators::NOT_EQUAL, value: $item))
                            ->toArray();

                        return ConditionTreeFactory::intersect($trees);
                    },
                ],
            ],
        ];
    }
}
