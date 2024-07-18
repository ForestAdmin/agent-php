<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\LoggerServices;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use Laravel\SerializableClosure\SerializableClosure;

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
    $customizer = $this->invokeProperty($agentFactory, 'customizer');

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
    $options = AGENT_OPTIONS;
    unset($options['envSecret']);
    $mockAgent = $this->getMockBuilder(AgentFactory::class)
        ->setConstructorArgs(['config' => $options])
        ->onlyMethods(['sendSchema'])
        ->getMock();
    $mockAgent->addDatasource($datasource);
    $mockAgent->build();

    $expected = new DecoratorsStack($datasource);
    $expected->build();

    expect(AgentFactory::get('datasource')->getCollections()->first())
        ->toEqual($expected->dataSource->getCollections()->first());
});

test('buildLogger() should call in construct and add logger to the agent container', function () {
    new AgentFactory(AGENT_OPTIONS);

    expect(AgentFactory::get('logger'))->toBeInstanceOf(LoggerServices::class);
});

test('createAgent() should add a new logger instance to the agent container', function () {
    $agent = new AgentFactory(AGENT_OPTIONS);
    $oldLogger = AgentFactory::get('logger');
    $agent->createAgent(
        [
            'loggerLevel' => 'Warning',
            'logger'      => fn () => null,
        ]
    );

    expect(AgentFactory::get('logger'))->not->toEqual($oldLogger);
});

test('createAgent() should add a serialized closure customizeErrorMessage to the agent container', function () {
    $agent = new AgentFactory(AGENT_OPTIONS);
    $agent->createAgent(['customizeErrorMessage' => fn () => 'customizeErrorMessage']);

    $closure = AgentFactory::get('customizeErrorMessage');
    expect($closure)->toBeInstanceOf(SerializableClosure::class)
        ->and($closure())->toEqual('customizeErrorMessage');
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
    $spy = \Mockery::spy(DatasourceCustomizer::class);
    $this->invokeProperty($agent, 'customizer', $spy);
    $agent->customizeCollection('name', fn () => true);
    $spy = $this->invokeProperty($agent, 'customizer');

    $spy->shouldHaveReceived('customizeCollection');
});

test('addChart() should work', function () {
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
    $spy = \Mockery::spy(DatasourceCustomizer::class);
    $this->invokeProperty($agent, 'customizer', $spy);
    $agent->addChart('myChart', fn () => true);
    $spy = $this->invokeProperty($agent, 'customizer');

    $spy->shouldHaveReceived('addChart');
});

test('use() should work', function () {
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'User');
    $datasource->addCollection($collectionUser);

    $agent = new AgentFactory(AGENT_OPTIONS);
    $spy = \Mockery::spy(DatasourceCustomizer::class);
    $this->invokeProperty($agent, 'customizer', $spy);
    $agent->use('MyFakePlugin');
    $spy = $this->invokeProperty($agent, 'customizer');

    $spy->shouldHaveReceived('use');
});
