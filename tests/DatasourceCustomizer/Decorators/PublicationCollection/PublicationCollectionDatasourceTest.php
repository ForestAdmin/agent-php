<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection\PublicationCollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function factoryPublicationCollectionDatasource($data = null)
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            'authorId' => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
            'author'   => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
            'persons'  => new ManyToManySchema(
                originKey: 'bookId',
                originKeyTarget: 'id',
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
                throughCollection: 'BookPerson',
            ),
        ]
    );

    $collectionBookPerson = new Collection($datasource, 'BookPerson');
    $collectionBookPerson->addFields(
        [
            'personId' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'bookId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'book'     => new ManyToOneSchema(
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
            'person'   => new ManyToOneSchema(
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
        ]
    );

    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'book'  => new OneToOneSchema(
                originKey: 'authorId',
                originKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
            'books' => new ManyToManySchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
                throughCollection: 'BookPerson',
            ),
        ]
    );

    if (isset($data)) {
        $create = $data['create'];
        unset($create[$data['unpublished']]);
        $collectionBook = mock($collectionBook)
            ->makePartial()
            ->shouldReceive('create')
            ->andReturn($create)
            ->getMock();
    }

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionBookPerson);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    $decoratedDataSource = new DatasourceDecorator($datasource, PublicationCollectionDecorator::class);
    $decoratedDataSource->build();

    $newBook = $decoratedDataSource->getCollection('Book');
    $newBookPersons = $decoratedDataSource->getCollection('BookPerson');
    $newPerson = $decoratedDataSource->getCollection('Person');

    return [$newBook, $newBookPersons, $newPerson, $datasource];
}

test('changeFieldVisibility() should throw when hiding a field which does not exists', function () {
    $newPerson = factoryPublicationCollectionDatasource()[2];

    expect(fn () => $newPerson->changeFieldVisibility('unknown', false))->toThrow(ForestException::class, 'Unknown field: unknown');
});

test('changeFieldVisibility() should throw when hiding the primary key', function () {
    $newPerson = factoryPublicationCollectionDatasource()[2];

    expect(fn () => $newPerson->changeFieldVisibility('id', false))->toThrow(ForestException::class, 'Cannot hide primary key');
});

test('the schema should be the same when doing nothing', function () {
    [$newBook, $newBookPersons, $newPerson, $datasource] = factoryPublicationCollectionDatasource();

    expect($newBook->getFields())->toEqual($datasource->getCollection('Book')->getFields())
        ->and($newBookPersons->getFields())->toEqual($datasource->getCollection('BookPerson')->getFields())
        ->and($newPerson->getFields())->toEqual($datasource->getCollection('Person')->getFields());
});

test('changeFieldVisibility() the schema should be the same when hiding and showing fields again', function () {
    [$newBook, $newBookPersons, $newPerson, $datasource] = factoryPublicationCollectionDatasource();
    $newPerson->changeFieldVisibility('book', false);
    $newPerson->changeFieldVisibility('book', true);

    expect($newPerson->getFields())->toEqual($datasource->getCollection('Person')->getFields());
});

test('changeFieldVisibility() when hiding normal fields the field should be removed from the schema of the collection', function () {
    $newBook = factoryPublicationCollectionDatasource()[0];
    $newBook->changeFieldVisibility('title', false);

    expect($newBook->getFields())->not()->toHaveKey('title');
});

test('changeFieldVisibility() when hiding normal fields other fields should not be affected', function () {
    $newBook = factoryPublicationCollectionDatasource()[0];
    $newBook->changeFieldVisibility('title', false);

    expect($newBook->getFields())->toHaveKeys(['id', 'authorId', 'author', 'persons']);
});

test('changeFieldVisibility() when hiding normal fields other collections should not be affected', function () {
    [$newBook, $newBookPersons, $newPerson, $datasource] = factoryPublicationCollectionDatasource();
    $newBook->changeFieldVisibility('title', false);

    expect($newBookPersons->getFields())->toEqual($datasource->getCollection('BookPerson')->getFields())
        ->and($newPerson->getFields())->toEqual($datasource->getCollection('Person')->getFields());
});

test('when hiding normal create() should proxies return value (removing extra columns)', function (Caller $caller) {
    $create = ['id' => 1, 'authorId' => 2, 'title' => 'Foundation'];
    $newBook = factoryPublicationCollectionDatasource(['create' => $create, 'unpublished' => 'title'])[0];
    $newBook->changeFieldVisibility('title', false);

    expect($newBook->create($caller, $create))->toEqual(['id' => 1, 'authorId' => 2]);
})->with('caller');

test('changeFieldVisibility() when hiding foreign keys the fk should be hidden', function () {
    $newBook = factoryPublicationCollectionDatasource()[0];
    $newBook->changeFieldVisibility('authorId', false);

    expect($newBook->getFields())->not()->toHaveKey('authorId');
});

test('changeFieldVisibility() all linked relations should be removed as well', function () {
    $newBook = factoryPublicationCollectionDatasource()[0];
    $newBook->changeFieldVisibility('authorId', false);

    expect($newBook->getFields())->not()->toHaveKey('author');
});
