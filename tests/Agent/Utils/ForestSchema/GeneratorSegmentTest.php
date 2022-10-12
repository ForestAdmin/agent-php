<?php


use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorSegment;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

test('buildSchema() should serialize segments', function () {
    $collection = new Collection(new Datasource(), 'Book');
    $collection->setSegments(['Active', 'Inactive']);

    expect(GeneratorSegment::buildSchema($collection, 'Active'))
        ->toEqual(['id' => 'Book.Active', 'name' => 'Active']);
});
