<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\ActionScope;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

it('should prevent instantiation when adding action with duplicated name', function () {
    $action = new ActionSchema(scope: ActionScope::single(), staticForm: true);
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addAction('__duplicated__', $action);
    $collection->addAction('__duplicated__', $action);

})->throws(Exception::class, 'Action __duplicated__ already defined in collection');

it('should add action with unique name',  function() {
    $expectedAction = new ActionSchema(scope: ActionScope::single(), staticForm: true);
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addAction('__action__', $expectedAction);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->getActions()['__action__'])->toEqual($expectedAction);
});

it('should prevent instantiation when adding field with duplicated name', function() {
    $field = new ColumnSchema(columnType: PrimitiveType::String());
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addField('__duplicated__', $field);
    $collection->addField('__duplicated__', $field);

})->throws(Exception::class, 'Field __duplicated__ already defined in collection');

it('should add field with unique name',  function() {
    $expectedField = new ColumnSchema(columnType: PrimitiveType::String());
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addField('__field__', $expectedField);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->getFields()->toArray())->toMatchArray($expectedField);
});

it('should add all fields',  function() {
    $expectedFields = [
        '__first__' => new ColumnSchema(
            columnType: PrimitiveType::Number(),
            isPrimaryKey: true
        ),
        '__second__' => new ColumnSchema(
            columnType: PrimitiveType::String(),
        ),
    ];
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addFields($expectedFields);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->getFields()->toArray())->toMatchArray($expectedFields);
});

it('should add all segments',  function() {
    $expectedSegments = ['__first__', '__second__'];
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addSegments($expectedSegments);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->getSegments()->toArray())->toMatchArray($expectedSegments);
});

it('should set searchable to true',  function() {
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->setSearchable(true);

    expect($collection)->toBeInstanceOf(Collection::class)
        ->and($collection->isSearchable())->toBe(true);
});


//it('should prevent execute unknown action',  function() {
//    $collection = new Collection(new Datasource(), '__collection__');
//    // todo execute is not define for now
//});

//it('should return an empty form',  function() {
//    $collection = new Collection(new Datasource(), '__collection__');
//
//});
