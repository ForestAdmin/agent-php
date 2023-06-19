<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ChartDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryChartCollectionDecorator()
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(['id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true)]);
    invokeProperty($collectionBook, 'charts', collect('childChart'));
    $collectionBook = mock($collectionBook)
        ->makePartial()
        ->shouldReceive('renderChart')
        ->andReturn(['countCurrent' => 1])
        ->getMock();

    $datasource->addCollection($collectionBook);
    buildAgent($datasource);

    $datasourceDecorator = new ChartDataSourceDecorator($datasource);
    $datasourceDecorator->build();

    return [$collectionBook, $datasourceDecorator->getCollection('Book')];
}

test('getCharts() should not be changed', function () {
    [$collectionBook, $decoratedBook] = factoryChartCollectionDecorator();

    expect($decoratedBook->getCharts())->toEqual($collectionBook->getCharts());
});

test('addChart() should throw an error if a chart already exists', function () {
    [, $decoratedBook] = factoryChartCollectionDecorator();

    expect(static fn () => $decoratedBook->addChart('childChart', fn () => true))
        ->toThrow(ForestException::class, "🌳🌳🌳 Chart 'childChart' already exists.");
});

test('renderChart() should call the child collection', function () {
    [, $decoratedBook] = factoryChartCollectionDecorator();
    $caller = QueryStringParser::parseCaller(Request::createFromGlobals());

    expect($decoratedBook->renderChart($caller, 'childChart', [123]))->toEqual(['countCurrent' => 1]);
});

test('closure should be called when rendering the chart', function () {
    [, $decoratedBook] = factoryChartCollectionDecorator();
    $decoratedBook->addChart('newChart', fn ($context, $resultBuilder) => ['countCurrent' => 2]);
    $caller = QueryStringParser::parseCaller(Request::createFromGlobals());

    expect($decoratedBook->renderChart($caller, 'newChart', [123]))->toEqual(['countCurrent' => 2]);
});
