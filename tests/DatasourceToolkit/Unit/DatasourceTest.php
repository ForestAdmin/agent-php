<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\DataSourceSchema;

it('should instantiate properly when extended', function () {
    class ConcreteDataSource extends Datasource
    {
    }

    $datasource = new ConcreteDataSource;

    expect($datasource)->toBeInstanceOf(ConcreteDataSource::class);
});

it('should expose collections from datasource as an Illuminate/Support/Collection', function () {
    $datasource = new Datasource();
    $datasource->addCollection(new Collection($datasource, '__collection__'));

    expect($datasource->getCollections())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});


it('should export an empty schema', function () {
    $datasource = new Datasource();

    expect($datasource->getSchema())->toBeInstanceOf(DataSourceSchema::class)
        ->and($datasource->getSchema())->toEqual(new DataSourceSchema);
});

it('should get collection from datasource', function () {
    $datasource = new Datasource();
    $expectedCollection = new Collection($datasource, '__collection__');
    $datasource->addCollection($expectedCollection);

    expect($datasource->getCollection($expectedCollection->getName()))->toBe($expectedCollection);
});

it('should fail to get collection if one with the same name is not present', function () {
    $datasource = new Datasource();
    $expectedCollection = new Collection($datasource, '__collection__');
    $datasource->addCollection($expectedCollection);
    $datasource->getCollection('__no_such_collection__');
})->throws(Exception::class, 'Collection __no_such_collection__ not found.');


//it('should throw if renderChart() is called', function () {
//    //todo
//});

it('should prevent instanciation when adding collection with duplicated name', function () {
    $datasource = new Datasource();
    $duplicatedCollection = new Collection($datasource, '__duplicated__');
    $datasource->addCollection($duplicatedCollection);
    $datasource->addCollection($duplicatedCollection);
})->throws(Exception::class, 'Collection __duplicated__ already defined in datasource');
