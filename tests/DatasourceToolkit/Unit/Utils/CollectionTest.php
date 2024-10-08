<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Utils\Collection as CollectionUtils;
use ForestAdmin\AgentPHP\Tests\TestCase;

describe('Datasource with Inverse relation missing', function () {
    $before = static function (TestCase $testCase) {
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

        $testCase->buildAgent($datasource);

        $testCase->bucket['datsource'] = $datasource;
    };

    test('getInverseRelation() should not find an inverse when inverse relations is missing', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datsource'];

        expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'author'))->toBeNull();
    });
});

describe('Datasource with all relations', function () {
    $before = static function (TestCase $testCase, array $args = []) {
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
                'comment' => new PolymorphicOneToManySchema(
                    originKey: 'commentableId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Comment',
                    originTypeField: 'commentableType',
                    originTypeValue: 'Book',
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
        $collectionComment = new Collection($datasource, 'Comment');
        $collectionComment->addFields(
            [
                'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'name'            => new ColumnSchema(columnType: PrimitiveType::STRING),
                'commentableId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER),
                'commentableType' => new ColumnSchema(columnType: PrimitiveType::STRING),
                'commentable'     => new PolymorphicManyToOneSchema(
                    foreignKeyTypeField: 'commentableType',
                    foreignKey: 'commentableId',
                    foreignKeyTargets: [
                        'Book' => 'id',
                    ],
                    foreignCollections: [
                        'Book',
                    ],
                ),
            ]
        );

        if (isset($args['Book']['list'])) {
            $collectionBook = \Mockery::mock($collectionBook)
                ->shouldReceive('list')
                ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
                ->andReturn($args['Book']['list'])
                ->getMock();
        }

        if (isset($args['BookPerson']['list'])) {
            $collectionBookPerson = \Mockery::mock($collectionBookPerson)
                ->shouldReceive('list')
                ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
                ->andReturn($args['BookPerson']['list'])
                ->getMock();
        }

        if (isset($args['Person']['list'])) {
            $collectionPerson = \Mockery::mock($collectionPerson)
                ->shouldReceive('list')
                ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Projection::class))
                ->andReturn($args['Person']['list'])
                ->getMock();
        }

        if (isset($args['BookPerson']['aggregate'])) {
            $collectionBookPerson = \Mockery::mock($collectionBookPerson)
                ->shouldReceive('aggregate')
                ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null)
                ->andReturn($args['BookPerson']['aggregate'])
                ->getMock();
        }

        if (isset($args['Person']['aggregate'])) {
            $collectionPerson = \Mockery::mock($collectionPerson)
                ->shouldReceive('aggregate')
                ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null)
                ->andReturn($args['Person']['aggregate'])
                ->getMock();
        }

        if (isset($args['Comment']['aggregate'])) {
            $collectionPerson = \Mockery::mock($collectionPerson)
                ->shouldReceive('aggregate')
                ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class), null)
                ->andReturn($args['Comment']['aggregate'])
                ->getMock();
        }

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionBookPerson);
        $datasource->addCollection($collectionPerson);
        $datasource->addCollection($collectionComment);

        $testCase->buildAgent($datasource);

        $testCase->bucket['datasource'] = $datasource;
    };

    test('getInverseRelation() should inverse a one to many relation in both directions', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];

        expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'myBookPersons'))->toEqual('myBook')
            ->and(CollectionUtils::getInverseRelation($datasource->getCollection('BookPerson'), 'myBook'))->toEqual('myBookPersons');
    });

    test('getInverseRelation() should inverse a many to many relation in both directions', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];

        expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'myPersons'))->toEqual('myBooks')
            ->and(CollectionUtils::getInverseRelation($datasource->getCollection('Person'), 'myBooks'))->toEqual('myPersons');
    });

    test('getInverseRelation() should inverse a one to one relation in both directions', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];

        expect(CollectionUtils::getInverseRelation($datasource->getCollection('Person'), 'myBookPerson'))->toEqual('myPerson')
            ->and(CollectionUtils::getInverseRelation($datasource->getCollection('BookPerson'), 'myPerson'))->toEqual('myBookPerson');
    });

    test('getInverseRelation() should inverse a polymorphic one to many relation', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];

        expect(CollectionUtils::getInverseRelation($datasource->getCollection('Book'), 'comment'))->toEqual('commentable');
    });

    test('isManyToManyInverse() should return false', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        /** @var Collection $collectionBook */
        $collectionBook = $datasource->getCollection('Book');
        $manyToManyRelation = new ManyToManySchema(
            originKey: 'fooId',
            originKeyTarget: 'id',
            foreignKey: 'bookId',
            foreignKeyTarget: 'id',
            foreignCollection: 'Book',
            throughCollection: 'BookFoo'
        );

        expect(CollectionUtils::isManyToManyInverse($collectionBook->getFields()['myPersons'], $manyToManyRelation))->toBeFalse();
    });

    test('isManyToManyInverse() should return true', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        /** @var Collection $collectionBook */
        $collectionBook = $datasource->getCollection('Book');
        $collectionPerson = $datasource->getCollection('Person');

        expect(CollectionUtils::isManyToManyInverse($collectionBook->getFields()['myPersons'], $collectionPerson->getFields()['myBooks']))->toBeTrue();
    });

    test('isManyToOneInverse() should return false', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        /** @var Collection $collectionBook */
        $collectionBookPerson = $datasource->getCollection('BookPerson');
        $collectionPerson = $datasource->getCollection('Person');

        expect(CollectionUtils::isManyToOneInverse($collectionBookPerson->getFields()['myBook'], $collectionPerson->getFields()['myBookPerson']))->toBeFalse();
    });

    test('isManyToOneInverse() should return true', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        /** @var Collection $collectionBook */
        $collectionBookPerson = $datasource->getCollection('BookPerson');
        $collectionBook = $datasource->getCollection('Book');

        expect(CollectionUtils::isManyToOneInverse($collectionBookPerson->getFields()['myBook'], $collectionBook->getFields()['myBookPersons']))->toBeTrue();
    });

    test('isOtherInverse() should return false', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        /** @var Collection $collectionBook */
        $collectionBookPerson = $datasource->getCollection('BookPerson');
        $collectionPerson = $datasource->getCollection('Person');

        expect(CollectionUtils::isOtherInverse($collectionPerson->getFields()['myBookPerson'], $collectionBookPerson->getFields()['myBook']))->toBeFalse();
    });

    test('isOtherInverse() should return true', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        /** @var Collection $collectionBook */
        $collectionBookPerson = $datasource->getCollection('BookPerson');
        $collectionPerson = $datasource->getCollection('Person');

        expect(CollectionUtils::isOtherInverse($collectionPerson->getFields()['myBookPerson'], $collectionBookPerson->getFields()['myPerson']))->toBeTrue();
    });

    test('getFieldSchema() should throw with unknown column', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionPerson = $datasource->getCollection('Person');

        expect(static fn () => CollectionUtils::getFieldSchema($collectionPerson, 'foo'))
            ->toThrow(ForestException::class, '🌳🌳🌳 Column not found Person.foo');
    });

    test('getFieldSchema() should work with simple column', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionPerson = $datasource->getCollection('Person');

        expect(CollectionUtils::getFieldSchema($collectionPerson, 'name'))->toEqual(
            new ColumnSchema(columnType: PrimitiveType::STRING)
        );
    });

    test('getFieldSchema() should throw with unknown relation:column', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionPerson = $datasource->getCollection('Person');

        expect(static fn () => CollectionUtils::getFieldSchema($collectionPerson, 'unknown:foo'))
            ->toThrow(ForestException::class, '🌳🌳🌳 Relation not found Person.unknown');
    });

    test('getFieldSchema() should throw with invalid relation type', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');

        expect(static fn () => CollectionUtils::getFieldSchema($collectionBook, 'myBookPersons:bookId'))
            ->toThrow(ForestException::class, '🌳🌳🌳 Unexpected field type OneToMany: Book.myBookPersons');
    });

    test('getFieldSchema() should work with relation column', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionBookPerson = $datasource->getCollection('BookPerson');

        expect(CollectionUtils::getFieldSchema($collectionBookPerson, 'myPerson:name'))->toEqual(
            new ColumnSchema(columnType: PrimitiveType::STRING)
        );
    });

    test('getThroughTarget() should throw with invalid relation type', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');

        expect(static fn () => CollectionUtils::getThroughTarget($collectionBook, 'myBookPersons'))
            ->toThrow(ForestException::class, '🌳🌳🌳 Relation must be many to man');
    });

    test('getThroughTarget() should work', function () use ($before) {
        $before($this);
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');

        expect(CollectionUtils::getThroughTarget($collectionBook, 'myPersons'))
            ->toEqual('myPerson');
    });

    test('getValue() should work', function (Caller $caller) use ($before) {
        $before(
            $this,
            [
                'Person' => [
                    'list' => ['id' => 1, 'name' => 'foo'],
                ],
            ]
        );
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Person');

        expect(CollectionUtils::getValue($collectionBook, $caller, [1], 'name'))
            ->toEqual('foo');
    })->with('caller');

    test('getValue() should work with composite id', function (Caller $caller) use ($before) {
        $before(
            $this,
            [
                'Book' => [
                    'list' => ['id' => 1, 'reference' => 'ref', 'title' => 'foo'],
                ],
            ]
        );
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');

        expect(CollectionUtils::getValue($collectionBook, $caller, [1, 'ref'], 'reference'))
            ->toEqual('ref');
    })->with('caller');

    test('listRelation() should work with one to many relation', function (Caller $caller) use ($before) {
        $before(
            $this,
            [
                'BookPerson' => [
                    'list' => [['bookId' => 1, 'personId' => 1]],
                ],
            ]
        );
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');
        $filter = new PaginatedFilter();

        expect(CollectionUtils::listRelation($collectionBook, [1], 'myBookPersons', $caller, $filter, new Projection()))
            ->toEqual([['bookId' => 1, 'personId' => 1]]);
    })->with('caller');

    test('listRelation() should work with many to many relation', function (Caller $caller) use ($before) {
        $before(
            $this,
            [
                'Person'     => [
                    'list' => [['id' => 1, 'name' => 'foo']],
                ],
                'BookPerson' => [
                    'list' => [['bookId' => 1, 'personId' => 1, 'myPerson' => 1, 'myBook' => 1]],
                ],
            ]
        );
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');
        $filter = new PaginatedFilter();

        expect(CollectionUtils::listRelation($collectionBook, [1], 'myPersons', $caller, $filter, new Projection()))
            ->toEqual([1]);
    })->with('caller');

    test('aggregateRelation() should work with one to many relation', function (Caller $caller) use ($before) {
        $before(
            $this,
            ['BookPerson' => ['aggregate' => 1]]
        );
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');
        $filter = new PaginatedFilter();

        expect(CollectionUtils::aggregateRelation($collectionBook, [1], 'myBookPersons', $caller, $filter, new Aggregation('Count')))
            ->toEqual(1);
    })->with('caller');

    test('aggregateRelation() should work with many to many relation', function (Caller $caller) use ($before) {
        $before(
            $this,
            ['Person' => ['aggregate' => 1], 'BookPerson' => ['aggregate' => 1]]
        );
        $datasource = $this->bucket['datasource'];
        $collectionBook = $datasource->getCollection('Book');
        $filter = new PaginatedFilter();

        expect(CollectionUtils::aggregateRelation($collectionBook, [1], 'myPersons', $caller, $filter, new Aggregation('Count')))
            ->toEqual(1);
    })->with('caller');
});
