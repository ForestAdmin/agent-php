<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Record as RecordUtils;

test('getPrimaryKeys() should find the pks from record', function () {
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addFields(
        [
            'id'      => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'notId'   => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'otherId' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
        ]
    );
    $result = RecordUtils::getPrimaryKeys($collection, ['id' => 1, 'notId' => 'foo', 'otherId' => 11]);

    expect($result)->toBeArray()
        ->and($result)->toEqual([1, 11]);
});

test('getPrimaryKeys() should throw if record has not PK', function () {
    $collection = new Collection(new Datasource(), '__collection__');
    $collection->addFields(
        [
            'id'      => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'notId'   => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
        ]
    );

    expect(static fn () => RecordUtils::getPrimaryKeys($collection, ['notId' => 'foo']))
        ->toThrow(ForestException::class, '🌳🌳🌳 Missing primary key: id');
});
