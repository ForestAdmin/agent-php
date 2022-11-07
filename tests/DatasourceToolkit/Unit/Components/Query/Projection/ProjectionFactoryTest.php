<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\ProjectionFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;

use function ForestAdmin\cache;

test('all() should return all the collection fields and the relation fields', function () {
    $datasource = datasourceWithOneToOneAndManyToOne();
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
    $datasource = datasourceOtherRelations();
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


function datasourceWithOneToOneAndManyToOne(): Datasource
{
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
                inverseRelationName: 'book'
            ),
            'myFormat' => new ManyToOneSchema(
                foreignKey: 'format_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'formats',
                inverseRelationName: 'books'
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

    $options = [
        'projectDir'   => sys_get_temp_dir(), // only use for cache
        'isProduction' => false,
    ];

    (new AgentFactory($options,  []))->addDatasource($datasource)->build();

    return $datasource;
}

function datasourceOtherRelations(): Datasource
{
    $datasource = new Datasource();
    $collectionBookPersons = new Collection($datasource, 'bookPersons');
    $collectionBookPersons->addFields(
        [
            'id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
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
                inverseRelationName: 'book'
            ),
        ]
    );

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionBookPersons);

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];

    (new AgentFactory($options,  []))->addDatasource($datasource)->build();

    return $datasource;
}
