<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

use Carbon\Carbon;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

final class Time
{
    public static function format(Carbon $value): string
    {
        return $value->setTimezone('UTC')->toIso8601String();
    }

    public static function compare(string $operator, \Closure $dateCallback): array
    {
        return [
            'dependsOn' => [$operator],
            'forTypes'  => ['Date', 'Dateonly'],
            'replacer'  => function ($leaf, $tz) use ($operator, $dateCallback) {
                $now = Carbon::now(tz: $tz);

                return $leaf->override(operator: $operator, value: self::format($dateCallback($now, $leaf->getValue())));
            },
        ];
    }

    public static function interval(\Closure $startFn, \Closure $endFn): array
    {
        return [
            'dependsOn' => [Operators::LESS_THAN, Operators::GREATER_THAN],
            'forTypes'  => ['Date', 'Dateonly'],
            'replacer'  => function ($leaf, $tz) use ($startFn, $endFn) {
                $now = Carbon::now(tz: $tz);

                return ConditionTreeFactory::intersect(
                    [
                        $leaf->override(operator: Operators::GREATER_THAN, value: self::format($startFn($now, $leaf->getValue()))),
                        $leaf->override(operator: Operators::LESS_THAN, value: self::format($endFn($now, $leaf->getValue()))),
                    ]
                );
            },
        ];
    }

    public static function previousInterval(string $duration): array
    {
        return self::interval(
            static fn ($now) => $now->sub[ucfirst($duration)](1)->startOf($duration),
            static fn ($now) => $now->startOf($duration),
        );
    }

    public static function previousIntervalToDate(string $duration): array
    {
        return self::interval(
            static fn ($now) => $now->startOf($duration),
            static fn ($now) => $now,
        );
    }

    public static function timeTransforms(): array
    {
        return [
            Operators::BEFORE => [self::compare(Operators::LESS_THAN, static fn ($now, $value) => Carbon::parse($value))],
            Operators::AFTER  => [self::compare(Operators::GREATER_THAN, static fn ($now, $value) => Carbon::parse($value))],

            Operators::PAST   => [self::compare(Operators::LESS_THAN, static fn ($now) => $now)],
            Operators::FUTURE => [self::compare(Operators::GREATER_THAN, static fn ($now) => $now)],

            Operators::BEFORE_X_HOURS_AGO => [self::compare(Operators::LESS_THAN, static fn ($now, $value) => $now->subHours($value))],
            Operators::AFTER_X_HOURS_AGO  => [self::compare(Operators::GREATER_THAN, static fn ($now, $value) => $now->subHours($value))],

            Operators::PREVIOUS_WEEK_TO_DATE    => [self::previousIntervalToDate('week')],
            Operators::PREVIOUS_MONTH_TO_DATE   => [self::previousIntervalToDate('month')],
            Operators::PREVIOUS_QUARTER_TO_DATE => [self::previousIntervalToDate('quarter')],
            Operators::PREVIOUS_YEAR_TO_DATE    => [self::previousIntervalToDate('year')],

            Operators::YESTERDAY        => [self::previousInterval('day')],
            Operators::PREVIOUS_WEEK    => [self::previousInterval('week')],
            Operators::PREVIOUS_MONTH   => [self::previousInterval('month')],
            Operators::PREVIOUS_QUARTER => [self::previousInterval('quarter')],
            Operators::PREVIOUS_YEAR    => [self::previousInterval('year')],

            Operators::PREVIOUS_X_DAYS_TO_DATE => [
                self::interval(
                    static fn ($now, $value) => $now->subDays($value)->startOfDay(),
                    static fn ($now) => $now,
                ),
            ],
            Operators::PREVIOUS_X_DAYS         => [
                self::interval(
                    static fn ($now, $value) => $now->subDays($value)->startOfDay(),
                    static fn ($now) => $now->startOfDay(),
                ),
            ],

            Operators::TODAY => [
                self::interval(
                    static fn ($now) => $now->startOfDay(),
                    static fn ($now) => $now->addDay()->startOfDay(),
                ),
            ],
        ];
    }
}
