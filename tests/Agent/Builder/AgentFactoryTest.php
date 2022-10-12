<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

function factoryAgentFactoryOptions(): array
{
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => SECRET,
        'isProduction' => false,
    ];

    return $options;
}

test('addDatasources() should add datasource & service to the container', function () {
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $datasource->addCollection($collectionUser);

    $agentFactory = new AgentFactory(factoryAgentFactoryOptions(), ['my-service' => 'foo']);
    $agentFactory->addDatasources([$datasource]);

    expect($agentFactory->get('datasource'))
        ->toEqual($datasource)
        ->and($agentFactory->get('datasource')->getCollections()->first())
        ->toEqual($collectionUser)
        ->and($agentFactory->get('my-service'))
        ->toEqual('foo');
});

test('addDatasources() should throw an Exception on a invalid datasource', function () {
    $datasource = new class () {};

    $agentFactory = new AgentFactory(factoryAgentFactoryOptions(), []);

    expect(fn () => $agentFactory->addDatasources([$datasource]))
        ->toThrow(\Exception::class, 'Invalid datasource');
});

test('renderChart() should add a datasource to the container', function () {
    $datasource = new Datasource();

    $agentFactory = new AgentFactory(factoryAgentFactoryOptions(), []);
    $agentFactory->addDatasources([$datasource]);
    $chart = new ValueChart(100, 10);

    expect($agentFactory->renderChart($chart)['data']['attributes'])
        ->toEqual(
            [
                'value' => [
                    'countCurrent'  => 100,
                    'countPrevious' => 10,
                ],
            ]
        );
});
