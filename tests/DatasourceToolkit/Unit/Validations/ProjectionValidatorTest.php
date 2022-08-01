<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ProjectionValidator;

dataset('collection', function () {
    yield $collection = new Collection(new Datasource(), 'books');
    $collection->addFields(
        [
            'id'     => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'author' => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'persons',
            ),
        ]
    );
});

it('should not throw if the field exist on the collection', function ($collection) {
    ProjectionValidator::validate($collection, new Projection(['id']));
})->expectNotToPerformAssertions()->with('collection');

it('should throw if the field is not of column type', function ($collection) {
    ProjectionValidator::validate($collection, new Projection(['author']));
})->throws(Exception::class, 'Unexpected field type: books.author (found ManyToOne expected \'Column\')')
    ->with('collection');
