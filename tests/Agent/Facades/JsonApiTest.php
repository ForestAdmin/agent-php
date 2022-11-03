<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\Agent\Services\JsonApiResponse;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

function factoryJsonApi()
{
    $datasource = new Datasource();
    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionPerson);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'schemaPath'   => sys_get_temp_dir() . '/.forestadmin-schema.json',
        'envSecret'    => AUTH_SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    SchemaEmitter::getSerializedSchema($datasource);

    return $collectionPerson;
}

test('renderCollection() should return a JsonApiResponse render', function () {
    factoryJsonApi();
    $content = [
        [
            'id'         => 1,
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ],
        [
            'id'         => 2,
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
        ],
    ];

    $render = JsonApi::renderCollection($content, new BasicArrayTransformer(), 'Person');

    expect($render)->toEqual((new JsonApiResponse())->renderCollection($content, new BasicArrayTransformer(), 'Person'));
});

test('renderItem() should return a JsonApiResponse renderItem', function () {
    factoryJsonApi();
    $content = [
        'id'         => 1,
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ];
    $render = JsonApi::renderItem($content, new BasicArrayTransformer(), 'Person');

    expect($render)->toEqual((new JsonApiResponse())->renderItem($content, new BasicArrayTransformer(), 'Person'));
});

test('deactivateCountResponse() should return a JsonApiResponse deactivateCountResponse', function () {
    factoryJsonApi();

    expect(JsonApi::deactivateCountResponse())->toEqual((new JsonApiResponse())->deactivateCountResponse());
});

test('renderChart() should add a datasource to the container', function () {
    $chart = new ValueChart(100, 10);
    $result = JsonApi::renderChart($chart);

    expect($result)
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['data'])
        ->toHaveKey('id')
        ->toHaveKey('value', (new JsonApiResponse())->renderChart($chart)['data']['value'])
        ->and($result['data']['id']);
});
