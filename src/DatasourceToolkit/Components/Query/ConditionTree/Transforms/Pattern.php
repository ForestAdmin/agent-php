<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

final class Pattern
{
    public static function likes(callable $getPattern, bool $caseSensitive): array
    {
        $operator = $caseSensitive ? Operators::LIKE : Operators::ILIKE;

        return [
            'dependsOn' => [$operator],
            'forTypes'  => ['String'],
            'replacer'  => fn ($leaf) => $leaf->override(operator: $operator, value: $getPattern($leaf->getValue())),
        ];
    }

    public static function patternTransforms(): array
    {
        return [
            Operators::CONTAINS     => [self::likes(fn ($value) => '%' . $value . '%', true)],
            Operators::STARTS_WITH  => [self::likes(fn ($value) => $value . '%', true)],
            Operators::ENDS_WITH    => [self::likes(fn ($value) => '%' . $value, true)],
            Operators::ICONTAINS    => [self::likes(fn ($value) => '%' . $value . '%', false)],
            Operators::ISTARTS_WITH => [self::likes(fn ($value) => $value . '%', false)],
            Operators::IENDS_WITH   => [self::likes(fn ($value) => '%' . $value, false)],
        ];
    }
}
