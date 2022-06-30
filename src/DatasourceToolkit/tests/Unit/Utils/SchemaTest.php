<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Schema as SchemaUtils;

it('should find the pks',  function() {
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'notId' => new ColumnSchema(
                columnType: PrimitiveType::String(),
            ),
            'otherId' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
        ]
    );
    $result = SchemaUtils::getPrimaryKeys($collection);

    expect($result)->toBeArray()
        ->and($result)->toEqual(['id', 'otherId']);
});

