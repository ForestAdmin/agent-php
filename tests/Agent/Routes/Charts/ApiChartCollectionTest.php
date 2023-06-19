<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\ApiChartCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Chart\ResultBuilder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factoryChartCollectionApi($args = []): ApiChartCollection
{
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'Book');
    $collectionBooks->addFields(['id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true)]);
    $datasource->addCollection($collectionBooks);
    $agent = buildAgent(new Datasource());
    $agent->addDatasource($datasource);

    $config = AgentFactory::getContainer()->get('cache')->get('config');
    unset($config['envSecret']);
    AgentFactory::getContainer()->get('cache')->put('config', $config, 3600);

    $agent->customizeCollection(
        'Book',
        fn (CollectionCustomizer $builder) => $builder
            ->addChart('myChart', fn ($context, ResultBuilder $resultBuilder) => $resultBuilder->value(34))
            ->addChart('mySmartChart', fn ($context) => [])
    );
    $agent->build();
    $_GET['record_id'] = 1;
    $request = Request::createFromGlobals();

    $chart = mock(ApiChartCollection::class)
        ->makePartial()
        ->shouldReceive('checkIp')
        ->getMock();

    invokeProperty($chart, 'collection', AgentFactory::get('datasource')->getCollection('Book'));
    invokeProperty($chart, 'request', $request);

    return $chart;
}

test('handleApiChart() should return an array', function () {
    $chartDatasourceApi = factoryChartCollectionApi();
    invokeProperty($chartDatasourceApi, 'chartName', 'myChart');
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
    $chartDatasourceApi = factoryChartCollectionApi();
    invokeProperty($chartDatasourceApi, 'chartName', 'mySmartChart');
    $result = $chartDatasourceApi->handleSmartChart();

    expect($result)->toBeArray()->and($result['content']);
});
