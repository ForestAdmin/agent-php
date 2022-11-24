<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms;

use Carbon\Carbon;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;

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
            'dependsOn' => ['LessThan', 'GreaterThan'],
            'forTypes'  => ['Date', 'Dateonly'],
            'replacer'  => function ($leaf, $tz) use ($startFn, $endFn) {
                $now = Carbon::now(tz: $tz);

                return ConditionTreeFactory::intersect(
                    [
                        $leaf->override(operator: 'GreaterThan', value: self::format($startFn($now, $leaf->getValue()))),
                        $leaf->override(operator: 'LessThan', value: self::format($endFn($now, $leaf->getValue()))),
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
            'Before' => [self::compare('LessThan', static fn ($now, $value) => Carbon::parse($value))],
            'After'  => [self::compare('GreaterThan', static fn ($now, $value) => Carbon::parse($value))],

            'Past'   => [self::compare('LessThan', static fn ($now) => $now)],
            'Future' => [self::compare('GreaterThan', static fn ($now) => $now)],

            'BeforeXHoursAgo' => [self::compare('LessThan', static fn ($now, $value) => $now->subHours($value))],
            'AfterXHoursAgo'  => [self::compare('GreaterThan', static fn ($now, $value) => $now->subHours($value))],

            'PreviousWeekToDate'    => [self::previousIntervalToDate('week')],
            'PreviousMonthToDate'   => [self::previousIntervalToDate('month')],
            'PreviousQuarterToDate' => [self::previousIntervalToDate('quarter')],
            'PreviousYearToDate'    => [self::previousIntervalToDate('year')],

            'Yesterday'       => [self::previousInterval('day')],
            'PreviousWeek'    => [self::previousInterval('week')],
            'PreviousMonth'   => [self::previousInterval('month')],
            'PreviousQuarter' => [self::previousInterval('quarter')],
            'PreviousYear'    => [self::previousInterval('year')],

            'PreviousXDaysToDate' => [
                self::interval(
                    static fn ($now, $value) => $now->subDays($value)->startOfDay(),
                    static fn ($now) => $now,
                ),
            ],
            'PreviousXDays'       => [
                self::interval(
                    static fn ($now, $value) => $now->subDays($value)->startOfDay(),
                    static fn ($now) => $now->startOfDay(),
                ),
            ],

            'Today' => [
                self::interval(
                    static fn ($now) => $now->startOfDay(),
                    static fn ($now) => $now->addDay()->startOfDay(),
                ),
            ],
        ];
    }
}
