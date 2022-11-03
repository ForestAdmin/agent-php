<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;

use function ForestAdmin\cache;

function projectionCollection(): Collection
{
    $collection = new Collection(new Datasource(), 'foo');
    $collection->addFields(
        [
            'id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    return $collection;
}

function collectionCompositePK(): Collection
{
    $collection = new Collection(new Datasource(), 'foo');
    $collection->addFields(
        [
            'key1' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'key2' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    return $collection;
}

function projectionDatasource(): Datasource
{
    $datasource = new Datasource();
    $collectionCars = new Collection($datasource, 'cars');
    $collectionCars->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'owner' => new OneToOneSchema(
                originKey: 'id',
                originKeyTarget: 'owner_id',
                foreignCollection: 'owners',
                inverseRelationName: 'car'
            ),
        ]
    );
    $collectionOwner = new Collection($datasource, 'owners');
    $collectionOwner->addFields(
        [
            'id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $datasource->addCollection($collectionCars);
    $datasource->addCollection($collectionOwner);

    $options = [
        'projectDir'      => sys_get_temp_dir(), // only use for cache
    ];
    (new AgentFactory($options,  []))->addDatasource($datasource)->build();

    return $datasource;
}

test('replaceItem() should remove duplicates', function () {
    $projection = new Projection(['id', 'name']);
    $projection = $projection->replaceItem(fn () => 'id');

    expect($projection)->toEqual(new Projection(['id']));
});

test('replaceItem() should allow replacing one field by many', function () {
    $projection = (new Projection(['id', 'name']))
        ->replaceItem(fn ($field) => $field === 'name' ? ['firstName', 'lastName'] : $field);

    expect($projection)->toEqual(new Projection(['id', 'firstName', 'lastName']));
});

test('apply() should reproject a list of records', function () {
    $projection = (new Projection(['id', 'name', 'author:name', 'other:id']));
    $result = $projection->apply(
        [
            [
                'id'     => 1,
                'name'   => 'romain',
                'age'    => 12,
                'author' => ['name' => 'ana', 'lastname' => 'something'],
                'other'  => null,
            ],
        ]
    );
    expect($result->toArray())->toEqual(
        [
            [
                "id"     => 1,
                "name"   => "romain",
                "author" => [
                    "name" => "ana",
                ],
                "other"  => null,
            ],
        ]
    );
});

test('nest() should do nothing with null', function () {
    $projection = (new Projection(['id', 'name', 'author:name', 'other:id']));

    expect($projection->nest(null))->toEqual($projection);
});

test('nest() should work with a prefix', function () {
    $projection = (new Projection(['id', 'name', 'author:name', 'other:id']));

    expect($projection->nest('prefix'))->toEqual(new Projection(
        [
            'prefix:id',
            'prefix:name',
            'prefix:author:name',
            'prefix:other:id',
        ]
    ));
});

test('unnest() should work when all paths share a prefix', function () {
    $projection = (new Projection(['id', 'name', 'author:name', 'other:id']));

    expect($projection->nest('prefix')->unnest())->toEqual($projection);
});

test('unnest() should throw when not possible', function () {
    $projection = (new Projection(['id', 'name', 'author:name', 'other:id']));

    expect($projection->unnest());
})->throws(Exception::class, 'Cannot unnest projection.');

test('withPks() should automatically add pks to the provided projection when the pk is a single field', function () {
    $collection = projectionCollection();
    $projection = (new Projection(['name']))->withPks($collection);

    expect($projection)->toEqual(new Projection(['name', 'id']));
});

test('withPks() should do nothing when the pks are already provided and the pk is a single field', function () {
    $collection = projectionCollection();
    $projection = (new Projection(['id', 'name']))->withPks($collection);

    expect($projection)->toEqual(new Projection(['id', 'name']));
});

test('withPks() should automatically add pks to the provided projection when the pk is a composite', function () {
    $collection = collectionCompositePK();
    $projection = (new Projection(['name']))->withPks($collection);

    expect($projection)->toEqual(new Projection(['name', 'key1', 'key2']));
});

test('should automatically add pks for all relations when dealing with projection using relationships', function () {
    $datasource = projectionDatasource();
    $collection = $datasource->getCollection('cars');
    $projection = (new Projection(['name', 'owner:name']))->withPks($collection);

    expect($projection)->toEqual(new Projection(['name', 'owner:name', 'id', 'owner:id']));
});

test('union() should work with two projections', function () {
    $projection1 = new Projection(['id', 'title']);
    $projection2 = new Projection(['id', 'amount']);

    expect($projection1->union($projection2))->toEqual(new Projection(
        [
            'id',
            'title',
            'amount',
        ]
    ));
});


test('union() should work with more two projections', function () {
    $projection1 = new Projection(['id', 'title']);
    $projection2 = new Projection(['id', 'amount']);
    $projection3 = new Projection(['id', 'category']);

    expect($projection1->union([$projection2, $projection3]))->toEqual(new Projection(
        [
            'id',
            'title',
            'amount',
            'category',
        ]
    ));
});

test('union() should work with a null projection', function () {
    $projection1 = new Projection(['id', 'title']);

    expect($projection1->union(null))->toEqual($projection1);
});
