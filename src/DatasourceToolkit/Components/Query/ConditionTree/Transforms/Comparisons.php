<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;

final class Comparisons
{
    public static function equalityTransforms(): array
    {
        return [
            'Blank'    => [
                [
                    'dependsOn' => ['In'],
                    'forTypes'  => ['String'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'In', value: [null, '']),
                ],
                [
                    'dependsOn' => ['Missing'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'Missing'),
                ],
            ],
            'Missing'  => [
                [
                    'dependsOn' => ['Equal'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'Equal', value: null),
                ],
            ],
            'Present'  => [
                [
                    'dependsOn' => ['NotIn'],
                    'forTypes'  => ['String'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'NotIn', value: [null, '']),
                ],
                [
                    'dependsOn' => ['NotEqual'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'NotEqual', value: null),
                ],
            ],
            'Equal'    => [
                [
                    'dependsOn' => ['In'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'In', value: [$leaf->getValue()]),
                ],
            ],
            'In'       => [
                [
                    'dependsOn' => ['Equal'],
                    'replacer'  => function ($leaf) {
                        $trees = collect($leaf->getValue())
                            ->map(fn ($item) => new ConditionTreeLeaf(field: $leaf->getField(), operator: 'Equal', value: $item))
                            ->toArray();

                        return ConditionTreeFactory::union($trees);
                    },
                ],
            ],
            'NotEqual' => [
                [
                    'dependsOn' => ['NotIn'],
                    'replacer'  => fn ($leaf) => $leaf->override(operator: 'NotIn', value: [$leaf->getValue()]),
                ],
            ],
            'NotIn'    => [
                [
                    'dependsOn' => ['NotEqual'],
                    'replacer'  => function ($leaf) {
                        $trees = collect($leaf->getValue)
                            ->map(fn ($item) => $leaf->override(operator: 'NotEqual', value: $item))
                            ->toArray();

                        return ConditionTreeFactory::intersect($trees);
                    },
                ],
            ],
        ];
    }
}
