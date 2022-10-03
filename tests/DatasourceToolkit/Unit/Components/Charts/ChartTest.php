<?php


use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LeaderboardChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\LineChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ObjectiveChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PercentageChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\PieChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\SmartChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

test('serialize() with simple ValueChart', function () {
    $chart = new ValueChart(10);

    expect($chart->serialize())->toEqual(
        [
            'countCurrent'  => 10,
            'countPrevious' => null,
        ]
    );
});

test('serialize() with ValueChart and count previous', function () {
    $chart = new ValueChart(10, 5);

    expect($chart->serialize())->toEqual(
        [
            'countCurrent'  => 10,
            'countPrevious' => 5,
        ]
    );
});

test('serialize() with PieChart', function () {
    $chart = new PieChart(
        [
            ['key' => 'key1', 'value' => 10],
            ['key' => 'key2', 'value' => 20],
        ]
    );

    expect($chart->serialize())->toEqual(
        [
            ['key' => 'key1', 'value' => 10],
            ['key' => 'key2', 'value' => 20],
        ]
    );
});

test('serialize() should throw with PieChart and invalid keys', function () {
    $chart = new PieChart(
        [
            ['foo' => 'key1', 'value' => 10],
            ['key' => 'key2', 'value' => 20],
        ]
    );

    expect(static fn () => $chart->serialize())
        ->toThrow(ForestException::class, "🌳🌳🌳 The result columns must be named ''key', 'value'' instead of 'foo,value'");
});

test('serialize() with PercentageChart', function () {
    $chart = new PercentageChart(10);

    expect($chart->serialize())->toEqual(10);
});

test('serialize() with simple ObjectiveChart', function () {
    $chart = new ObjectiveChart(10);

    expect($chart->serialize())->toEqual(
        [
            'value' => 10,
        ]
    );
});

test('serialize() with ObjectiveChart and objective', function () {
    $chart = new ObjectiveChart(10, 50);

    expect($chart->serialize())->toEqual(
        [
            'value'     => 10,
            'objective' => 50,
        ]
    );
});

test('serialize() with LineChart', function () {
    $chart = new LineChart(
        [
            ['label' => 'key1', 'values' => 10],
            ['label' => 'key2', 'values' => 20],
        ]
    );

    expect($chart->serialize())->toEqual(
        [
            ['label' => 'key1', 'values' => 10],
            ['label' => 'key2', 'values' => 20],
        ]
    );
});

test('serialize() should throw with LineChart and invalid keys', function () {
    $chart = new LineChart(
        [
            ['foo' => 'key1', 'value' => 10],
            ['key' => 'key2', 'value' => 20],
        ]
    );

    expect(static fn () => $chart->serialize())
        ->toThrow(ForestException::class, "🌳🌳🌳 The result columns must be named ''label', 'values'' instead of 'foo,value'");
});

test('serialize() with LeaderboardChart', function () {
    $chart = new LeaderboardChart(
        [
            ['key'   => 'key1', 'value' => 10],
            ['key'   => 'key2', 'value' => 20],
        ]
    );

    expect($chart->serialize())->toEqual(
        [
            ['key' => 'key1', 'value' => 10],
            ['key' => 'key2', 'value' => 20],
        ]
    );
});

test('serialize() should throw with LeaderboardChart and invalid keys', function () {
    $chart = new LeaderboardChart(
        [
            ['foo' => 'key1', 'value' => 10],
            ['key' => 'key2', 'value' => 20],
        ]
    );

    expect(static fn () => $chart->serialize())
        ->toThrow(ForestException::class, "🌳🌳🌳 The result columns must be named ''key', 'value'' instead of 'foo,value'");
});

test('serialize() with smartChart', function () {
    $chart = new smartChart(
        ['label' => 'smart', 'value' => 'chart'],
    );

    expect($chart->serialize())->toEqual(
        ['label' => 'smart', 'value' => 'chart'],
    );
});
