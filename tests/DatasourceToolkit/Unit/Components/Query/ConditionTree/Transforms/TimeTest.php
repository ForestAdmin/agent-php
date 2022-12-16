<?php

use Carbon\Carbon;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Transforms\Time;

use function Spatie\PestPluginTestTime\testTime;

test('Before should rewrite', function () {
    $timeTransforms = Time::timeTransforms();
    expect($timeTransforms[Operators::BEFORE][0]['replacer'](new ConditionTreeLeaf('column', Operators::BEFORE, Carbon::now()), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now())));
});

test('After should rewrite', function () {
    $timeTransforms = Time::timeTransforms();
    expect($timeTransforms[Operators::AFTER][0]['replacer'](new ConditionTreeLeaf('column', Operators::AFTER, Carbon::now()), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now())));
});

test('Past should rewrite', function () {
    $timeTransforms = Time::timeTransforms();
    expect($timeTransforms[Operators::PAST][0]['replacer'](new ConditionTreeLeaf('column', Operators::PAST, Carbon::now()), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now())));
});

test('Future should rewrite', function () {
    $timeTransforms = Time::timeTransforms();
    expect($timeTransforms[Operators::FUTURE][0]['replacer'](new ConditionTreeLeaf('column', Operators::FUTURE, Carbon::now()), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now())));
});

test('BeforeXHoursAgo should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();
    expect($timeTransforms[Operators::BEFORE_X_HOURS_AGO][0]['replacer'](new ConditionTreeLeaf('column', Operators::BEFORE_X_HOURS_AGO, 24), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now()->subDay())));
});

test('AfterXHoursAgo should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();
    expect($timeTransforms[Operators::AFTER_X_HOURS_AGO][0]['replacer'](new ConditionTreeLeaf('column', Operators::AFTER_X_HOURS_AGO, 24), 'Europe/Paris'))
        ->toEqual(new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now()->subDay())));
});

test('PreviousMonthToDate should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_MONTH_TO_DATE][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_MONTH_TO_DATE),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('month'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris'))),
            ]
        )
    );
});

test('PreviousMonth should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_MONTH][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_MONTH),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subMonth()->startOf('month'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('month'))),
            ]
        )
    );
});

test('PreviousQuarterToDate should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_QUARTER_TO_DATE][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_QUARTER_TO_DATE),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('quarter'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris'))),
            ]
        )
    );
});

test('PreviousQuarter should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_QUARTER][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_QUARTER),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subQuarter()->startOf('quarter'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('quarter'))),
            ]
        )
    );
});

test('PreviousWeekToDate should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_WEEK_TO_DATE][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_WEEK_TO_DATE),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('week'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris'))),
            ]
        )
    );
});

test('PreviousWeek should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_WEEK][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_WEEK),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subWeek()->startOf('week'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('week'))),
            ]
        )
    );
});

test('PreviousXDaysToDate should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_X_DAYS_TO_DATE][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_X_DAYS_TO_DATE, 14),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subDays(14)->startOfDay())),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris'))),
            ]
        )
    );
});

test('PreviousXDays should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_X_DAYS][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_X_DAYS, 14),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subDays(14)->startOfDay())),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->startOfDay())),
            ]
        )
    );
});

test('PreviousYearToDate should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_YEAR_TO_DATE][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_YEAR_TO_DATE),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('year'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris'))),
            ]
        )
    );
});

test('PreviousYear should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::PREVIOUS_YEAR][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::PREVIOUS_YEAR),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subYear()->startOf('year'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('year'))),
            ]
        )
    );
});

test('Today should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::TODAY][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::TODAY),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->startOfDay())),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->addDay()->startOfDay())),
            ]
        )
    );
});

test('Yesterday should rewrite', function () {
    testTime()->freeze(Carbon::now());
    $timeTransforms = Time::timeTransforms();

    expect(
        $timeTransforms[Operators::YESTERDAY][0]['replacer'](
            new ConditionTreeLeaf('column', Operators::YESTERDAY),
            'Europe/Paris'
        )
    )->toEqual(
        new ConditionTreeBranch(
            'And',
            [
                new ConditionTreeLeaf('column', Operators::GREATER_THAN, Time::format(Carbon::now('Europe/Paris')->subDay()->startOf('day'))),
                new ConditionTreeLeaf('column', Operators::LESS_THAN, Time::format(Carbon::now('Europe/Paris')->startOf('day'))),
            ]
        )
    );
});
