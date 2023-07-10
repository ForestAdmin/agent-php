<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\ProjectionFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

test('all() should return all the collection fields and the relation fields', function () {
    $datasource = new Datasource();
    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'format_id' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'title'     => new ColumnSchema(columnType: PrimitiveType::STRING),
            'myAuthor'  => new OneToOneSchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'authors',
            ),
            'myFormat'  => new ManyToOneSchema(
                foreignKey: 'format_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'formats',
            ),
        ]
    );
    $collectionAuthors = new Collection($datasource, 'authors');
    $collectionAuthors->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            'book_id' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $collectionFormats = new Collection($datasource, 'formats');
    $collectionFormats->addFields(
        [
            'id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionAuthors);
    $datasource->addCollection($collectionFormats);

    buildAgent($datasource);
    $collection = $datasource->getCollection('books');

    expect(ProjectionFactory::all($collection))->toEqual(
        new Projection(
            [
                'id',
                'format_id',
                'title',
                'myAuthor:id',
                'myAuthor:name',
                'myAuthor:book_id',
                'myFormat:id',
                'myFormat:name',
            ]
        )
    );
});


test('all() should return all the collection fields without the relations', function () {
    $datasource = new Datasource();
    $collectionBookPersons = new Collection($datasource, 'bookPersons');
    $collectionBookPersons->addFields(
        [
            'id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
        ]
    );

    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'         => new ColumnSchema(columnType: PrimitiveType::STRING),
            'myBookPersons' => new OneToManySchema(
                originKey: 'id',
                originKeyTarget: 'id',
                foreignCollection: 'bookPersons',
            ),
        ]
    );

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionBookPersons);

    buildAgent($datasource);

    $collection = $datasource->getCollection('books');

    expect(ProjectionFactory::all($collection))->toEqual(
        new Projection(
            [
                'id',
                'title',
            ]
        )
    );
});
