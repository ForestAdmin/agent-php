<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use Illuminate\Support\Str;

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

    private function like(?string $value, string $pattern, bool $caseSensitive): bool
    {
        if (! $value) {
            return false;
        }

        $regexp = Str::of($pattern)->replaceMatches('/([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-])/', '\\$1')
            ->replaceMatches('/%/', '.*')
            ->replaceMatches('/_/', '.');

        return preg_match_all('#^'.$regexp.'\z#' . ($caseSensitive ? '' : 'i'), $value) === 1;
    }

    public static function match(bool $caseSensitive): array
    {
        return [
            'dependsOn' => [Operators::MATCH],
            'forTypes'  => ['String'],
            'replacer'  => function ($leaf) use ($caseSensitive) {
                $regex = Str::of($leaf->getValue())->replaceMatches('/([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-])/', '\\$1')
                    ->replaceMatches('/%/', '.*')
                    ->replaceMatches('/_/', '.');

                return $leaf->override(operator: Operators::MATCH, value: '/^'.$regex.'$/' . ($caseSensitive ? '' : 'i'));
            },
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
            Operators::ILIKE        => [self::match(false)],
            Operators::LIKE         => [self::match(true)],
        ];
    }
}
