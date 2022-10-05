<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\Agent\Services\JsonApiResponse;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
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
        'envSecret'    => SECRET,
        'isProduction' => false,
        'agentUrl'     => 'http://localhost/',
    ];
    (new AgentFactory($options, []))->addDatasources([$datasource]);

    return $collectionPerson;
}

test('render() should return a JsonApiResponse render', function () {
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

    $render = JsonApi::render($content, new BasicArrayTransformer(), 'Person');

    expect($render)->toEqual((new JsonApiResponse())->render($content, new BasicArrayTransformer(), 'Person'));
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
