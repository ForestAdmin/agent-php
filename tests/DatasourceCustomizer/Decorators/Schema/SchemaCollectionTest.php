<?php

namespace ForestAdmin\AgentPHP\Tests\DatasourceCustomizer\Decorators\Schema;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\SchemaCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

function factorySchemaCollection()
{
    $datasource = new Datasource();
    $collectionProduct = new Collection($datasource, 'Product');
    $collectionProduct->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'  => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL, Operators::IN]),
        ]
    );

    $datasource->addCollection($collectionProduct);
    buildAgent($datasource);

    return [$collectionProduct, $datasource];
}

test('should overwrite fields from the schema', function () {
    [$collection, $datasource] = factorySchemaCollection();

    $schemaCollection = new SchemaCollection($collection, $datasource);
    $schemaCollection->overrideSchema('countable', false);

    expect($collection->isCountable())->toBeTrue()
        ->and($schemaCollection->isCountable())->toBeFalse();
});

test('should not overwrite fields from the schema when the overrideSchema is not called', function () {
    [$collection, $datasource] = factorySchemaCollection();

    $schemaCollection = new SchemaCollection($collection, $datasource);

    expect($collection->isCountable())->toBeTrue()
        ->and($schemaCollection->isCountable())->toBeTrue();
});
