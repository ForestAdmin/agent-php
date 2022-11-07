<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\Id;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

function factoryIdUtil()
{
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'Person');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $collectionFoo = new Collection($datasource, 'Foo');
    $collectionFoo->addFields(
        [
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionFoo);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'authSecret'    => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    return compact('collectionUser', 'collectionFoo');
}

test('packId() should return the id value', function () {
    $collectionUser = factoryIdUtil()['collectionUser'];

    $packId = Id::packId($collectionUser, [
        'id'  => 1,
        'foo' => 'bar',
    ]);

    expect($packId)->toEqual(1);
});

test('packId() throw when collection doesn\'t have any primary keys', function () {
    $collectionFoo = factoryIdUtil()['collectionFoo'];

    expect(fn () => Id::packId($collectionFoo, [
        'id'  => 1,
        'foo' => 'bar',
    ]))->toThrow(ForestException::class, '🌳🌳🌳 This collection has no primary key');
});

test('packIds()  should return an array of ids', function () {
    $collectionUser = factoryIdUtil()['collectionUser'];

    $packIds = Id::packIds($collectionUser, [
        [
            'id'  => 1,
            'foo' => 'bar',
        ],
        [
            'id'  => 2,
            'foo' => 'foo',
        ],
    ]);

    expect($packIds)->toEqual([1, 2]);
});

test('unpackId() should return the list of id values', function () {
    $collectionUser = factoryIdUtil()['collectionUser'];

    expect(Id::unpackId($collectionUser, '1,2'))->toEqual(['1,2']);
});

test('unpackId() throw when count of getPrimaryKeys is different of the number of pk values', function () {
    $collectionFoo = factoryIdUtil()['collectionFoo'];

    expect(fn () => Id::unpackId($collectionFoo, '1,2'))->toThrow(ForestException::class, '🌳🌳🌳 Expected $primaryKeyNames a size of 0 values, found 1');
});

test('unpackIds() should return an array of list id valus', function () {
    $collectionUser = factoryIdUtil()['collectionUser'];

    $unpackIds = Id::unpackIds($collectionUser, [1, 2]);

    expect($unpackIds)->toEqual([[1], [2]]);
});

test('de', function () {
    $_GET['data'] = [
        'attributes' => [
            'ids'                      => ['1','2','3'],
            'collection_name'          => 'User',
            'parent_collection_name'   => null,
            'parent_collection_id'     => null,
            'parent_association_name'  => null,
            'all_records'              => true,
            'all_records_subset_query' => [
                'fields[Car]'      => 'id,first_name,last_name',
                'page[number]'     => 1,
                'page[size]'       => 15,
            ],
            'all_records_ids_excluded' => ['4'],
            'smart_action_id'          => null,
        ],
        'type'       => 'action-requests',
    ];

    $request = Request::createFromGlobals();
    $collectionUser = factoryIdUtil()['collectionUser'];
    $selectionIds = Id::parseSelectionIds($collectionUser, $request);

    expect($selectionIds)->toEqual(['areExcluded' => true, 'ids' => [[4]]]);
});
