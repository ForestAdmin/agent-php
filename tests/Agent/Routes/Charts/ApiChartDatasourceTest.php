<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\ApiChartDatasource;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ResultBuilder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Mockery;

beforeEach(function () {
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'Book');
    $datasource->addCollection($collectionBooks);
    $this->buildAgent($datasource);

    $config = AgentFactory::getContainer()->get('cache')->get('config');
    unset($config['envSecret']);
    AgentFactory::getContainer()->get('cache')->put('config', $config, 3600);

    $this->agent->addChart('myChart', fn ($context, ResultBuilder $resultBuilder) => $resultBuilder->value(34));
    $this->agent->addChart('mySmartChart', fn ($context) => []);
    $this->agent->build();

    $request = Request::createFromGlobals();

    $chart = Mockery::mock(ApiChartDatasource::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    $this->invokeProperty($chart, 'request', $request);
    $this->invokeProperty($chart, 'datasource', AgentFactory::get('datasource'));

    $this->bucket['chart'] = $chart;
});

test('handleApiChart() should return an array', function () {
    $chartDatasourceApi = $this->bucket['chart'];
    $this->invokeProperty($chartDatasourceApi, 'chartName', 'myChart');
    $result = $chartDatasourceApi->handleApiChart();

    expect($result)
        ->toBeArray()
        ->and($result['content'])
        ->toBeArray()
        ->and($result['content']['data'])
        ->toBeArray()
        ->and($result['content']['data'])
        ->toHaveKeys(['id', 'type', 'attributes'])
        ->and($result['content']['data']['type'])
        ->toEqual('stats')
        ->and($result['content']['data']['attributes']['value'])
        ->toEqual((new ResultBuilder())->value(34)->serialize());
});

test('handleSmartChart() should return an array', function () {
    $chartDatasourceApi = $this->bucket['chart'];
    $this->invokeProperty($chartDatasourceApi, 'chartName', 'mySmartChart');
    $result = $chartDatasourceApi->handleSmartChart();

    expect($result)->toBeArray()->and($result['content']);
});
