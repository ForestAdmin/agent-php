<?php
//
//use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
//use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
//use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
//use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
//use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
//
//function factoryAgentFactoryOptions(): array
//{
//    $options = [
//        'projectDir'   => sys_get_temp_dir(),
//        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
//        'authSecret'    => AUTH_SECRET,
//        'isProduction'  => false,
//    ];
//
//    return $options;
//}
//
//test('addDatasource() should add datasource to the container', function () {
//    $datasource = new Datasource();
//    $collectionUser = new Collection($datasource, 'User');
//    $collectionUser->addFields(
//        [
//            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
//            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
//            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
//        ]
//    );
//    $datasource->addCollection($collectionUser);
//
//    $agentFactory = new AgentFactory(factoryAgentFactoryOptions(), ['my-service' => 'foo']);
//    $agentFactory->addDatasource($datasource)->build();
//
//    expect($agentFactory->get('datasource'))
//        ->toEqual($datasource)
//        ->and($agentFactory->get('datasource')->getCollections()->first())
//        ->toEqual($collectionUser)
//        ->and($agentFactory->get('my-service'))
//        ->toEqual('foo');
//});
//
//test('addDatasources() should throw an Exception on a invalid datasource', function () {
//    $datasource = new class () {};
//
//    $agentFactory = new AgentFactory(factoryAgentFactoryOptions(), []);
//
//    expect(fn () => $agentFactory->addDatasource($datasource)->build())
//        ->toThrow(\Exception::class, 'Invalid datasource');
//});
