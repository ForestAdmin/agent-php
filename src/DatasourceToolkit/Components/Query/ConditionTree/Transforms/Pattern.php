<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

final class Pattern
{
    public static function likes(callable $getPattern, bool $caseSensitive): array
    {
        $operator = $caseSensitive ? 'Like' : 'ILike';

        return [
            'dependsOn' => ['operator'],
            'forTypes'  => ['String'],
            'replacer'  => fn ($leaf) => $leaf->override(operator: $operator, value: $getPattern($leaf->getValue())),
        ];
    }

    public static function patternTransforms(): array
    {
        return [
            'Contains'    => [self::likes(['value' => '%$value%'], true)],
            'StartsWith'  => [self::likes(['value' => '$value%'], true)],
            'EndsWith'    => [self::likes(['value' => '%$value'], true)],
            'IContains'   => [self::likes(['value' => '%$value%'], false)],
            'IStartsWith' => [self::likes(['value' => '$value%'], false)],
            'IEndsWith'   => [self::likes(['value' => '%$value'], false)],
        ];
    }
}
