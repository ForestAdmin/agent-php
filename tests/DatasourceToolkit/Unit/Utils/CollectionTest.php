<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;

function dataSourceWithInverseRelationMissing(): Datasource
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'       => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'author'   => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
            'authorId' => new ColumnSchema(
                columnType: PrimitiveType::UUID,
            ),
        ]
    );
    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
        ]
    );
    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionPerson);

    buildAgent($datasource);

    return $datasource;
}

function datasourceWithAllRelations(array $args = []): Datasource
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'            => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
            'reference'     => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
            'title'         => new ColumnSchema(columnType: PrimitiveType::STRING),
            'myPersons'     => new ManyToManySchema(
                originKey: 'bookId',
                originKeyTarget: 'id',
                throughTable: 'bookPerson',
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
                throughCollection: 'BookPerson'
            ),
            'myBookPersons' => new OneToManySchema(
                originKey: 'bookId',
                originKeyTarget: 'id',
                foreignCollection: 'BookPerson',
            ),
        ]
    );
    $collectionBookPerson = new Collection($datasource, 'BookPerson');
    $collectionBookPerson->addFields(
        [
            'bookId'   => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'personId' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'myBook'   => new ManyToOneSchema(
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
            ),
            'myPerson' => new ManyToOneSchema(
                foreignKey: 'personId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
            ),
        ]
    );
    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'           => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: [Operators::EQUAL, Operators::IN],
                isPrimaryKey: true
            ),
            'name'         => new ColumnSchema(columnType: PrimitiveType::STRING),
            'myBooks'      => new ManyToManySchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                throughTable: 'bookPerson',
                foreignKey: 'bookId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Book',
                throughCollection: 'BookPerson'

            ),
            'myBookPerson' => new OneToOneSchema(
                originKey: 'personId',
                originKeyTarget: 'id',
                foreignCollection: 'BookPerson',
            ),
        ]
    );

    if (isset($args['Book']['list'])) {
        $collectionBook = mock($collectionBook)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['Book']['list'])
            ->getMock();
    }

    if (isset($args['BookPerson']['list'])) {
        $collectionBookPerson = mock($collectionBookPerson)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['BookPerson']['list'])
            ->getMock();
    }

    if (isset($args['Person']['list'])) {
        $collectionPerson = mock($collectionPerson)
            ->shouldReceive('list')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
            ->andReturn($args['Person']['list'])
            ->getMock();
    }

    if (isset($args['BookPerson']['aggregate'])) {
        $collectionBookPerson = mock($collectionBookPerson)
            ->shouldReceive('aggregate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null)
            ->andReturn($args['BookPerson']['aggregate'])
            ->getMock();
    }

    if (isset($args['Person']['aggregate'])) {
        $collectionPerson = mock($collectionPerson)
            ->shouldReceive('aggregate')
            ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null)
            ->andReturn($args['Person']['aggregate'])
            ->getMock();
    }

    $datasource->addCollection($collectionBook);
    $datasource->addCollection($collectionBookPerson);
    $datasource->addCollection($collectionPerson);

    buildAgent($datasource);

    return $datasource;
}

test('getInverseRelation() should not find an inverse when inverse relations is missing', function () {
    $datasource = dataSourceWithInverseRelationMissing();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'author'))->toBeNull();
});

test('getInverseRelation() should inverse a one to many relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'myBookPersons'))->toEqual('myBook')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('BookPerson'), 'myBook'))->toEqual('myBookPersons');
});

test('getInverseRelation() should inverse a many to many relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'myPersons'))->toEqual('myBooks')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('Person'), 'myBooks'))->toEqual('myPersons');
});

test('getInverseRelation() should inverse a one to one relation in both directions', function () {
    $datasource = datasourceWithAllRelations();

    expect(CollectionUtils::getInverseRelation($datasource->getCollection('Person'), 'myBookPerson'))->toEqual('myPerson')
        ->and(CollectionUtils::getInverseRelation($datasource->getCollection('BookPerson'), 'myPerson'))->toEqual('myBookPerson');
});

test('isManyToManyInverse() should return false', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBook */
    $collectionBook = $datasource->getCollection('Book');
    $manyToManyRelation = new ManyToManySchema(
        originKey: 'fooId',
        originKeyTarget: 'id',
        throughTable: 'bookFoo',
        foreignKey: 'bookId',
        foreignKeyTarget: 'id',
        foreignCollection: 'Book',
        throughCollection: 'BookFoo'
    );

    expect(CollectionUtils::isManyToManyInverse($collectionBook->getFields()['myPersons'], $manyToManyRelation))->toBeFalse();
});

test('isManyToManyInverse() should return true', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBook */
    $collectionBook = $datasource->getCollection('Book');
    $collectionPerson = $datasource->getCollection('Person');

    expect(CollectionUtils::isManyToManyInverse($collectionBook->getFields()['myPersons'], $collectionPerson->getFields()['myBooks']))->toBeTrue();
});

test('isManyToOneInverse() should return false', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBook */
    $collectionBookPerson = $datasource->getCollection('BookPerson');
    $collectionPerson = $datasource->getCollection('Person');

    expect(CollectionUtils::isManyToOneInverse($collectionBookPerson->getFields()['myBook'], $collectionPerson->getFields()['myBookPerson']))->toBeFalse();
});

test('isManyToOneInverse() should return true', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBook */
    $collectionBookPerson = $datasource->getCollection('BookPerson');
    $collectionBook = $datasource->getCollection('Book');

    expect(CollectionUtils::isManyToOneInverse($collectionBookPerson->getFields()['myBook'], $collectionBook->getFields()['myBookPersons']))->toBeTrue();
});

test('isOtherInverse() should return false', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBook */
    $collectionBookPerson = $datasource->getCollection('BookPerson');
    $collectionPerson = $datasource->getCollection('Person');

    expect(CollectionUtils::isOtherInverse($collectionPerson->getFields()['myBookPerson'], $collectionBookPerson->getFields()['myBook']))->toBeFalse();
});

test('isOtherInverse() should return true', function () {
    $datasource = datasourceWithAllRelations();
    /** @var Collection $collectionBook */
    $collectionBookPerson = $datasource->getCollection('BookPerson');
    $collectionPerson = $datasource->getCollection('Person');

    expect(CollectionUtils::isOtherInverse($collectionPerson->getFields()['myBookPerson'], $collectionBookPerson->getFields()['myPerson']))->toBeTrue();
});

test('getFieldSchema() should throw with unknown column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionPerson = $datasource->getCollection('Person');

    expect(static fn () => CollectionUtils::getFieldSchema($collectionPerson, 'foo'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Column not found Person.foo');
});

test('getFieldSchema() should work with simple column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionPerson = $datasource->getCollection('Person');

    expect(CollectionUtils::getFieldSchema($collectionPerson, 'name'))->toEqual(
        new ColumnSchema(columnType: PrimitiveType::STRING)
    );
});

test('getFieldSchema() should throw with unknown relation:column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionPerson = $datasource->getCollection('Person');

    expect(static fn () => CollectionUtils::getFieldSchema($collectionPerson, 'unknown:foo'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation not found Person.unknown');
});

test('getFieldSchema() should throw with invalid relation type', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBook = $datasource->getCollection('Book');

    expect(static fn () => CollectionUtils::getFieldSchema($collectionBook, 'myBookPersons:bookId'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Unexpected field type OneToMany: Book.myBookPersons');
});

test('getFieldSchema() should work with relation column', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBookPerson = $datasource->getCollection('BookPerson');

    expect(CollectionUtils::getFieldSchema($collectionBookPerson, 'myPerson:name'))->toEqual(
        new ColumnSchema(columnType: PrimitiveType::STRING)
    );
});

test('getThroughTarget() should throw with invalid relation type', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBook = $datasource->getCollection('Book');

    expect(static fn () => CollectionUtils::getThroughTarget($collectionBook, 'myBookPersons'))
        ->toThrow(ForestException::class, '🌳🌳🌳 Relation must be many to man');
});

test('getThroughTarget() should work', function () {
    $datasource = datasourceWithAllRelations();
    $collectionBook = $datasource->getCollection('Book');

    expect(CollectionUtils::getThroughTarget($collectionBook, 'myPersons'))
        ->toEqual('myPerson');
});

test('getValue() should work', function (Caller $caller) {
    $datasource = datasourceWithAllRelations(
        [
            'Person' => [
                'list' => ['id' => 1, 'name' => 'foo'],
            ],
        ]
    );
    $collectionBook = $datasource->getCollection('Person');

    expect(CollectionUtils::getValue($collectionBook, $caller, [1], 'name'))
        ->toEqual('foo');
})->with('caller');

test('getValue() should work with composite id', function (Caller $caller) {
    $datasource = datasourceWithAllRelations(
        [
            'Book' => [
                'list' => ['id' => 1, 'reference' => 'ref', 'title' => 'foo'],
            ],
        ]
    );
    $collectionBook = $datasource->getCollection('Book');

    expect(CollectionUtils::getValue($collectionBook, $caller, [1, 'ref'], 'reference'))
        ->toEqual('ref');
})->with('caller');

test('listRelation() should work with one to many relation', function (Caller $caller) {
    $datasource = datasourceWithAllRelations(
        [
            'BookPerson' => [
                'list' => [['bookId' => 1, 'personId' => 1]],
            ],
        ]
    );
    $collectionBook = $datasource->getCollection('Book');
    $filter = new PaginatedFilter();

    expect(CollectionUtils::listRelation($collectionBook, [1], 'myBookPersons', $caller, $filter, new Projection()))
        ->toEqual([['bookId' => 1, 'personId' => 1]]);
})->with('caller');

test('listRelation() should work with many to many relation', function (Caller $caller) {
    $datasource = datasourceWithAllRelations(
        [
            'Person'     => [
                'list' => [['id' => 1, 'name' => 'foo']],
            ],
            'BookPerson' => [
                'list' => [['bookId' => 1, 'personId' => 1, 'myPerson' => 1, 'myBook' => 1]],
            ],
        ]
    );
    $collectionBook = $datasource->getCollection('Book');
    $filter = new PaginatedFilter();

    expect(CollectionUtils::listRelation($collectionBook, [1], 'myPersons', $caller, $filter, new Projection()))
        ->toEqual([1]);
})->with('caller');

test('aggregateRelation() should work with one to many relation', function (Caller $caller) {
    $datasource = datasourceWithAllRelations(['BookPerson' => ['aggregate' => 1]]);
    $collectionBook = $datasource->getCollection('Book');
    $filter = new PaginatedFilter();

    expect(CollectionUtils::aggregateRelation($collectionBook, [1], 'myBookPersons', $caller, $filter, new Aggregation('Count')))
        ->toEqual(1);
})->with('caller');

test('aggregateRelation() should work with many to many relation', function (Caller $caller) {
    $datasource = datasourceWithAllRelations(['Person' => ['aggregate' => 1], 'BookPerson' => ['aggregate' => 1]]);
    $collectionBook = $datasource->getCollection('Book');
    $filter = new PaginatedFilter();

    expect(CollectionUtils::aggregateRelation($collectionBook, [1], 'myPersons', $caller, $filter, new Aggregation('Count')))
        ->toEqual(1);
})->with('caller');
