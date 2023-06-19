<?php


use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function GeneratorFieldWithOneToOneRelation(): Datasource
{
    $datasource = new Datasource();

    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'authorId'     => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
            'author'       => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
        ]
    );

    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'book'         => new OneToOneSchema(
                originKey: 'authorId',
                originKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    return $datasource;
}

function GeneratorFieldWithOneToManyRelation(): Datasource
{
    $datasource = new Datasource();

    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'authorId'     => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
            'author'       => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
        ]
    );

    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'books'         => new OneToManySchema(
                originKey: 'authorId',
                originKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    return $datasource;
}

function GeneratorFieldWithManyToManyRelation(): Datasource
{
    $datasource = new Datasource();

    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'persons'      => new ManyToManySchema(
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
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'books'         => new ManyToManySchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
                throughCollection: 'BookPerson',
            ),
        ]
    );

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionBookPerson);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);

    return $datasource;
}

test('buildSchema() should generate relation One to One', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithOneToOneRelation()->getCollection('Person'),
        'book'
    );

    expect($schema)->toEqual(
        [
            'defaultValue' => null,
            'enums'        => null,
            'field'        => 'book',
            'integration'  => null,
            'inverseOf'    => 'author',
            'isFilterable' => false,
            'isPrimaryKey' => false,
            'isReadOnly'   => false,
            'isRequired'   => false,
            'isSortable'   => true,
            'isVirtual'    => false,
            'reference'    => 'Book.id',
            'relationship' => 'HasOne',
            'type'         => 'Number',
            'validations'  => [],
        ]
    );
});

test('buildSchema() should generate inverse relation One to One', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithOneToOneRelation()->getCollection('Book'),
        'author'
    );

    expect($schema)->toEqual(
        [
            'defaultValue' => null,
            'enums'        => null,
            'field'        => 'author',
            'integration'  => null,
            'inverseOf'    => 'book',
            'isFilterable' => false,
            'isPrimaryKey' => false,
            'isReadOnly'   => false,
            'isRequired'   => false,
            'isSortable'   => true,
            'isVirtual'    => false,
            'reference'    => 'Person.id',
            'relationship' => 'BelongsTo',
            'type'         => 'Number',
            'validations'  => [],
        ]
    );
});

test('buildSchema() should generate relation One to Many', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithOneToManyRelation()->getCollection('Person'),
        'books'
    );

    expect($schema)->toEqual(
        [
            'defaultValue' => null,
            'enums'        => null,
            'field'        => 'books',
            'integration'  => null,
            'inverseOf'    => 'author',
            'isFilterable' => false,
            'isPrimaryKey' => false,
            'isReadOnly'   => false,
            'isRequired'   => false,
            'isSortable'   => false,
            'isVirtual'    => false,
            'reference'    => 'Book.id',
            'relationship' => 'HasMany',
            'type'         => ['Number'],
            'validations'  => [],
        ]
    );
});

test('buildSchema() should generate inverse relation One to Many', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithOneToManyRelation()->getCollection('Book'),
        'author'
    );

    expect($schema)->toEqual(
        [
            'defaultValue' => null,
            'enums'        => null,
            'field'        => 'author',
            'integration'  => null,
            'inverseOf'    => 'books',
            'isFilterable' => false,
            'isPrimaryKey' => false,
            'isReadOnly'   => false,
            'isRequired'   => false,
            'isSortable'   => true,
            'isVirtual'    => false,
            'reference'    => 'Person.id',
            'relationship' => 'BelongsTo',
            'type'         => 'Number',
            'validations'  => [],
        ]
    );
});

test('buildSchema() should generate relation when inverse is defined', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithManyToManyRelation()->getCollection('Book'),
        'persons'
    );

    expect($schema)->toEqual(
        [
            'defaultValue' => null,
            'enums'        => null,
            'field'        => 'persons',
            'integration'  => null,
            'inverseOf'    => 'books',
            'isFilterable' => false,
            'isPrimaryKey' => false,
            'isReadOnly'   => false,
            'isRequired'   => false,
            'isSortable'   => false,
            'isVirtual'    => false,
            'reference'    => 'Person.id',
            'relationship' => 'BelongsToMany',
            'type'         => ['Number'],
            'validations'  => [],
        ]
    );
});

test('buildSchema() should generate relation as primary key', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithManyToManyRelation()->getCollection('BookPerson'),
        'book'
    );

    expect($schema)->toEqual(
        [
            'defaultValue' => null,
            'enums'        => null,
            'field'        => 'book',
            'integration'  => null,
            'inverseOf'    => null,
            'isFilterable' => false,
            'isPrimaryKey' => false,
            'isReadOnly'   => false,
            'isRequired'   => false,
            'isSortable'   => true,
            'isVirtual'    => false,
            'reference'    => 'Book.id',
            'relationship' => 'BelongsTo',
            'type'         => 'Number',
            'validations'  => [],
        ]
    );
});

test('buildSchema() should sort schema property', function () {
    $schema = GeneratorField::buildSchema(
        GeneratorFieldWithManyToManyRelation()->getCollection('BookPerson'),
        'book'
    );

    expect(array_keys($schema))->toEqual(
        [
            'defaultValue',
            'enums',
            'field',
            'integration',
            'inverseOf',
            'isFilterable',
            'isPrimaryKey',
            'isReadOnly',
            'isRequired',
            'isSortable',
            'isVirtual',
            'reference',
            'relationship',
            'type',
            'validations',
        ]
    );
});
