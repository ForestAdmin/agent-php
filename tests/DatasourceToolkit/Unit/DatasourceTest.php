<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Psr\Log\LoggerInterface;

it('should instantiate properly when extended', function () {
    class ConcreteDataSource extends Datasource
    {
    }

    $datasource = new ConcreteDataSource();

    expect($datasource)->toBeInstanceOf(ConcreteDataSource::class);
});

it('should expose collections from datasource as an Illuminate/Support/Collection', function () {
    $datasource = new Datasource();
    $datasource->addCollection(new Collection($datasource, '__collection__'));

    expect($datasource->getCollections())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});


it('should export an empty charts', function () {
    $datasource = new Datasource();

    expect($datasource->getCharts())->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($datasource->getCharts())->toEqual(collect());
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

it('should call logger when adding collection with duplicated name', function () {
    $agent = buildAgent(new  Datasource());
    // Create the mock
    $mockLogger = $this->createMock(LoggerInterface::class);
    // Expect a call to info, with the e-mail address in the message
    $mockLogger->expects($this->once())
        ->method('info')
        ->with($this->stringContains('Collection __duplicated__ already defined in datasource'));
    $agent->setLogger($mockLogger);

    $datasource = new Datasource();
    $duplicatedCollection = new Collection($datasource, '__duplicated__');
    $datasource->addCollection($duplicatedCollection);
    $datasource->addCollection($duplicatedCollection);
});

it('should throw when call renderChart', function ($caller) {
    $datasource = new Datasource();
    expect(fn () => $datasource->renderChart($caller, 'myChart'))->toThrow(ForestException::class);
})->with('caller');
