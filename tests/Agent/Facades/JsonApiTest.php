<?php

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\Agent\Services\JsonApiResponse;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

beforeEach(function () {
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
    $this->buildAgent($datasource);
    SchemaEmitter::getSerializedSchema($datasource);
});

test('renderCollection() should return a JsonApiResponse render', function () {
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

    $render = JsonApi::renderCollection($content, new BasicArrayTransformer(), 'Person', Request::createFromGlobals());

    expect($render)->toEqual((new JsonApiResponse())->renderCollection($content, new BasicArrayTransformer(), 'Person', Request::createFromGlobals()));
});

test('renderItem() should return a JsonApiResponse renderItem', function () {
    $content = [
        'id'         => 1,
        'first_name' => 'John',
        'last_name'  => 'Doe',
    ];
    $render = JsonApi::renderItem($content, new BasicArrayTransformer(), 'Person');

    expect($render)->toEqual((new JsonApiResponse())->renderItem($content, new BasicArrayTransformer(), 'Person'));
});

test('deactivateCountResponse() should return a JsonApiResponse deactivateCountResponse', function () {
    expect(JsonApi::deactivateCountResponse())->toEqual((new JsonApiResponse())->deactivateCountResponse());
});

test('renderChart() should return a JsonApiResponse', function () {
    $chart = new ValueChart(100, 10);
    $result = JsonApi::renderChart($chart);

    expect($result)
        ->toBeArray()
        ->toHaveKey('data')
        ->and($result['data'])
        ->toHaveKey('id')
        ->toHaveKey('attributes')
        ->and($result['data']['attributes'])
        ->toHaveKey('value', (new JsonApiResponse())->renderChart($chart)['data']['attributes']['value'])
        ->and($result['data']['id']);
});
