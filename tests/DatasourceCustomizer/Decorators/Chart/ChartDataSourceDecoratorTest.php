<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ChartDataSourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

\Ozzie\Nest\describe('ChartDecorator', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id1'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
                'id2'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            ]
        );
        $datasource->addCollection($collectionBook);
        $this->buildAgent($datasource);

        $datasourceDecorator = new ChartDataSourceDecorator($datasource);
        $datasourceDecorator->build();

        $this->bucket['datasourceDecorator'] = $datasourceDecorator;
    });

    test('getCharts() should return empty array with no charts', function () {
        $chartDatasource = $this->bucket['datasourceDecorator'];

        expect($chartDatasource->getCharts())->toEqual(collect());
    });

    test('getCharts() should return charts', function () {
        $chartDatasource = $this->bucket['datasourceDecorator'];
        $chartDatasource->addChart('myChart', fn ($context, $resultBuilder) => $resultBuilder->value(34));

        expect($chartDatasource->getCharts())->toEqual(collect(['myChart']));
    });

    test('addChart() should throw an error if a chart already exists', function () {
        $chartDatasource = $this->bucket['datasourceDecorator'];
        $chartDatasource->addChart('myChart', fn ($context, $resultBuilder) => $resultBuilder->value(34));

        expect(static fn () => $chartDatasource->addChart('myChart', fn () => true))
            ->toThrow(ForestException::class, "🌳🌳🌳 Chart 'myChart' already exists.");
    });

    test('renderChart() should call the closure and return chart', function () {
        $chartDatasource = $this->bucket['datasourceDecorator'];
        $chartDatasource->addChart('myChart', fn ($context, $resultBuilder) => $resultBuilder->value(34));
        $request = Request::createFromGlobals();

        expect($chartDatasource->renderChart(QueryStringParser::parseCaller($request), 'myChart'))
            ->toEqual(new ValueChart(34));
    });

    test('renderChart() should call the parent renderChart if chart not exist and throw', function () {
        $chartDatasource = $this->bucket['datasourceDecorator'];
        $request = Request::createFromGlobals();

        expect(static fn () => $chartDatasource->renderChart(QueryStringParser::parseCaller($request), 'fooChart'))
            ->toThrow(ForestException::class, "🌳🌳🌳 No chart named 'fooChart' exists on this datasource.");
    });

    test('addChart() should throw an error if a chart already exists When decorating a datasource with charts', function () {
        $datasource = new Datasource();
        invokeProperty($datasource, 'charts', collect(['myChart']));
        $collectionBook = new Collection($datasource, 'Book');
        $datasource->addCollection($collectionBook);
        $this->buildAgent($datasource);
        $chartDatasource = new ChartDataSourceDecorator($datasource);
        $chartDatasource->build();

        expect(static fn () => $chartDatasource->addChart('myChart', fn () => true))
            ->toThrow(ForestException::class, "🌳🌳🌳 Chart 'myChart' already exists.");
    });

    test('When adding charts on a lower layer getCharts() should throw when adding a duplicate', function () {
        $datasource = new Datasource();
        $this->buildAgent($datasource);
        $chartDatasource1 = new ChartDataSourceDecorator($datasource);
        $chartDatasource1->build();
        $chartDatasource2 = new ChartDataSourceDecorator($chartDatasource1);
        $chartDatasource2->build();
        $chartDatasource2->addChart('myChart', fn () => true);
        $chartDatasource1->addChart('myChart', fn () => true);


        expect(static fn () => $datasource->getCharts())
            ->not()->toThrow(ForestException::class)
            ->and(static fn () => $chartDatasource1->getCharts())
            ->not()->toThrow(ForestException::class)
            ->and(static fn () => $chartDatasource2->getCharts())
            ->toThrow(ForestException::class, "🌳🌳🌳 Chart 'myChart' is defined twice.");
    });
});
