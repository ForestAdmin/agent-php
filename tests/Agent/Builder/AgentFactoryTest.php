<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

test('addDatasource() should add datasource to the datasourceCustomizer', function () {
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
    $agentFactory = new AgentFactory(AGENT_OPTIONS);
    $agentFactory->addDatasource($datasource);
    /** @var DatasourceCustomizer $customizer */
    $customizer = invokeProperty($agentFactory, 'customizer');

    expect($customizer->getStack()->dataSource->getCollections()->first()->getName())
        ->toEqual('User');
});

test('build() should add datasource to the container', function () {
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
    $mockAgent = $this->getMockBuilder(AgentFactory::class)
        ->setConstructorArgs(['config' => AGENT_OPTIONS])
        ->onlyMethods(['sendSchema'])
        ->getMock();
    $mockAgent->expects($this->atLeast(1))
        ->method('sendSchema');
    $mockAgent->addDatasource($datasource);
    $mockAgent->build();

    $expected = new DecoratorsStack($datasource);
    $expected->build();

    expect(AgentFactory::get('datasource')->getCollections()->first())
        ->toEqual($expected->dataSource->getCollections()->first());
});

test('create agent with services should add services to the container', function () {
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
    new AgentFactory(AGENT_OPTIONS, ['my_service' => 'foo']);

    expect(AgentFactory::get('my_service'))->toEqual('foo');
});

test('customizeCollection() should work', function () {
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
    $agent = new AgentFactory(AGENT_OPTIONS);
    $spy = Mockery::spy(DatasourceCustomizer::class);
    invokeProperty($agent, 'customizer', $spy);
    $agent->customizeCollection('name', fn () => true);
    $spy = invokeProperty($agent, 'customizer');

    $spy->shouldHaveReceived('customizeCollection');
});
