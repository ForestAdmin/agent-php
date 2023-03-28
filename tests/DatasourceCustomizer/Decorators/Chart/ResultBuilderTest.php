<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ResultBuilder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PercentageChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;

test('value() could return the expected format', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->value(34))
        ->toEqual(new ValueChart(34))
        ->and($resultBuilder->value(34, 45))
        ->toEqual(new ValueChart(34, 45));
});

test('distribution() could return the expected format', function () {
    $resultBuilder = new ResultBuilder();
    $data = ['a' => 10, 'b' => 11];
    $result = collect($data)
        ->map(fn ($value, $key) => compact('key', 'value'))
        ->toArray();

    expect($resultBuilder->distribution(['a' => 10, 'b' => 11]))
        ->toEqual(new PieChart($result));
});

test('timeBased() could return the expected format (Day)', function () {
    $resultBuilder = new ResultBuilder();
    $data = [
        '1985-10-27' => 2,
        '1985-10-26' => 1,
        '1985-10-30' => 3,
    ];
    $result = [
        ['label' => '26/10/1985', 'values' => ['value' => 1]],
        ['label' => '27/10/1985', 'values' => ['value' => 2]],
        ['label' => '28/10/1985', 'values' => ['value' => 0]],
        ['label' => '29/10/1985', 'values' => ['value' => 0]],
        ['label' => '30/10/1985', 'values' => ['value' => 3]],
    ];

    expect($resultBuilder->timeBased('Day', $data))
        ->toEqual(new LineChart($result));
});

test('timeBased() could return the expected format (Week)', function () {
    $resultBuilder = new ResultBuilder();
    $data = [
        '1985-12-26' => 1,
        '1986-01-08' => 4,
        '1986-01-07' => 3,
    ];
    $result = [
        ['label' => 'W52-1985', 'values' => ['value' => 1]],
        ['label' => 'W01-1986', 'values' => ['value' => 0]],
        ['label' => 'W02-1986', 'values' => ['value' => 7]],
    ];

    expect($resultBuilder->timeBased('Week', $data))
        ->toEqual(new LineChart($result));
});

test('timeBased() could return the expected format (Month)', function () {
    $resultBuilder = new ResultBuilder();
    $data = [
        '1985-10-26' => 1,
        '1985-11-27' => 2,
        '1986-01-07' => 3,
        '1986-01-08' => 4,
    ];
    $result = [
        ['label' => 'Oct 1985', 'values' => ['value' => 1]],
        ['label' => 'Nov 1985', 'values' => ['value' => 2]],
        ['label' => 'Dec 1985', 'values' => ['value' => 0]],
        ['label' => 'Jan 1986', 'values' => ['value' => 7]],
    ];

    expect($resultBuilder->timeBased('Month', $data))
        ->toEqual(new LineChart($result));
});

test('timeBased() could return the expected format (Year)', function () {
    $resultBuilder = new ResultBuilder();
    $data = [
        '1985-10-26' => 1,
        '1986-01-07' => 3,
        '1986-01-08' => 4,
    ];
    $result = [
        ['label' => '1985', 'values' => ['value' => 1]],
        ['label' => '1986', 'values' => ['value' => 7]],
    ];

    expect($resultBuilder->timeBased('Year', $data))
        ->toEqual(new LineChart($result));
});

test('percentage() could return the expected format', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->percentage(34))
        ->toEqual(new PercentageChart(34));
});

test('objective() could return the expected format', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->objective(34, 45))
        ->toEqual(new ObjectiveChart(34, 45));
});

test('leaderboard() could return the expected format', function () {
    $resultBuilder = new ResultBuilder();
    $data = [
        'a' => 10,
        'b' => 30,
        'c' => 20,
    ];
    $result = [
        ['key' => 'b', 'value' => 30],
        ['key' => 'c', 'value' => 20],
        ['key' => 'a', 'value' => 10],
    ];

    expect($resultBuilder->leaderboard($data))
        ->toEqual(new LeaderboardChart($result));
});

test('smart() could return the expected format', function () {
    $resultBuilder = new ResultBuilder();
    expect($resultBuilder->smart(34))
        ->toEqual(34);
});
