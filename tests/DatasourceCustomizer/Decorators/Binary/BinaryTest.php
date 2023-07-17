<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Binary\BinaryCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

\Ozzie\Nest\describe('BinaryCollectin', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionFavorite = new Collection($datasource, 'Favorite');
        $collectionFavorite->addFields(
            [
                'id'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'book' => new ManyToOneSchema(
                    foreignKey: 'book_id',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
            ]
        );

        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::BINARY, isPrimaryKey: true, validation: [
                    ['operator' => Operators::LONGER_THAN, 'value' => 15],
                    ['operator' => Operators::SHORTER_THAN, 'value' => 17],
                    ['operator' => Operators::PRESENT],
                    ['operator' => Operators::NOT_EQUAL, 'value' => bin2hex('123456')],
                ]),
                'title' => new ColumnSchema(columnType: PrimitiveType::STRING),
                'cover' => new ColumnSchema(columnType: PrimitiveType::BINARY),
            ]
        );

        $datasource->addCollection($collectionFavorite);
        $datasource->addCollection($collectionBook);
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, BinaryCollection::class);
        $datasourceDecorator->build();

        $this->bucket = [$datasourceDecorator, $collectionFavorite, $collectionBook];
    });

    test('setBinaryMode() should throw if an invalid mode is provided', function () {
        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        expect(fn () => $decoratedBook->setBinaryMode('cover', 'invalid'))->toThrow(\Exception::class, 'Invalid binary mode');
    });

    test('setBinaryMode() should throw if the field does not exist', function () {
        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        expect(fn () => $decoratedBook->setBinaryMode('invalid', 'hex'))->toThrow(\Exception::class, 'Undefined array key "invalid"');
    });

    test('setBinaryMode() should throw if the field is not a binary field', function () {
        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        expect(fn () => $decoratedBook->setBinaryMode('title', 'hex'))->toThrow(\Exception::class, 'Expected a binary field');
    });

    test('setBinaryMode() should not throw if the field is a binary field', function () {
        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        expect($decoratedBook->setBinaryMode('cover', 'hex'))->toBeNull();
    });

    test('favorite schema should not be modified', function () {
        [$datasourceDecorator, $favorite, $book] = $this->bucket;

        expect($favorite->getFields())->toEqual($datasourceDecorator->getCollection('Favorite')->getFields());
    });

    test('book primary key should be rewritten as an hex string', function () {
        $datasourceDecorator = $this->bucket[0];
        $id = $datasourceDecorator->getCollection('Book')->getFields()['id'];

        expect($id->isPrimaryKey())->toBeTrue()
            ->and($id->getColumnType())->toEqual(PrimitiveType::STRING)
            ->and($id->getValidation())->toEqual(
                [
                    ['operator' => Operators::MATCH, 'value' => '/^[0-9a-f]+$/'],
                    ['operator' => Operators::LONGER_THAN, 'value' => 31],
                    ['operator' => Operators::SHORTER_THAN, 'value' => 33],
                    ['operator' => Operators::PRESENT],
                ]
            );
    });

    test('book author should be rewritten as a datauri', function () {
        $datasourceDecorator = $this->bucket[0];
        $cover = $datasourceDecorator->getCollection('Book')->getFields()['cover'];

        expect($cover->getColumnType())->toEqual(PrimitiveType::STRING)
            ->and($cover->getValidation())->toEqual(
                [
                    ['operator' => Operators::MATCH, 'value' => '/^data:.*;base64,.*/'],
                ]
            );
    });

    test('if requested, cover should be rewritten as a datauri', function () {
        $datasourceDecorator = $this->bucket[0];
        $datasourceDecorator->getCollection('Book')->setBinaryMode('cover', 'datauri');
        $cover = $datasourceDecorator->getCollection('Book')->getFields()['cover'];

        expect($cover->getColumnType())->toEqual(PrimitiveType::STRING)
            ->and($cover->getValidation())->toEqual(
                [
                    ['operator' => Operators::MATCH, 'value' => '/^data:.*;base64,.*/'],
                ]
            );
    });

    test('list with a simple filter - query should be transformed', function (Caller $caller) {
        $conditionTree = new ConditionTreeLeaf('id', Operators::EQUAL, '30303030');
        $filter = new PaginatedFilter(conditionTree: $conditionTree);
        $projection = new Projection(['id', 'cover']);

        $datasourceDecorator = $this->bucket[0];
        $datasourceDecorator->getCollection('Book')->getFields();
        $expectedConditionTree = new ConditionTreeLeaf('id', Operators::EQUAL, hex2bin('30303030'));

        $decoratedBook = $datasourceDecorator->getCollection('Book');
        $datasourceDecorator->getCollection('Book')->getFields();

        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('list')
            ->once()
            ->withArgs(function ($caller, $filter) use ($expectedConditionTree) {
                if (is_resource($filter->getConditionTree()->getValue())) {
                    return stream_get_contents($filter->getConditionTree()->getValue()) === $expectedConditionTree->getValue();
                }
            })
            ->andReturn([])
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->list($caller, $filter, $projection);
    })->with('caller');

    test('list with a simple filter - records should be transformed', function (Caller $caller) {
        $conditionTree = new ConditionTreeLeaf('id', Operators::EQUAL, '30303030');
        $filter = new PaginatedFilter(conditionTree: $conditionTree);
        $projection = new Projection(['id', 'cover']);

        [$datasourceDecorator, $favorite, $book] = $this->bucket;
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        $id = hex2bin('1234');
        $fp = fopen('php://temp', 'rb+');
        fwrite($fp, $id);
        fseek($fp, 0);
        $id = $fp;

        $file = file_get_contents(__DIR__ . '/transparent.gif');
        $fp = fopen('php://temp', 'rb+');
        fwrite($fp, $file);
        fseek($fp, 0);
        $cover = $fp;

        $records = [
            [
                'id'    => $id,
                'title' => 'Foundation',
                'cover' => $cover,
            ],
        ];

        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->shouldReceive('list')
            ->andReturn($records)
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->getFields();

        expect($decoratedBook->list($caller, $filter, $projection))->toEqual([
            [
                'id'    => '1234',
                'title' => 'Foundation',
                'cover' => 'data:image/gif;base64,' . base64_encode($file),
            ],
        ]);
    })->with('caller');

    test('list with a more complex filter - query should be transformed', function (Caller $caller) {
        $conditionTree = new ConditionTreeBranch('Or', [
            new ConditionTreeLeaf('id', Operators::EQUAL, '30303030'),
            new ConditionTreeLeaf('id', Operators::IN, ['30303030']),
            new ConditionTreeLeaf('title', Operators::EQUAL, 'Foundation'),
            new ConditionTreeLeaf('title', Operators::LIKE, 'Found%'),
            new ConditionTreeLeaf('cover', Operators::EQUAL, 'data:image/gif;base64,' . base64_encode('123')),
        ]);

        $filter = new PaginatedFilter(conditionTree: $conditionTree);
        $projection = new Projection(['id', 'cover']);

        $datasourceDecorator = $this->bucket[0];
        $datasourceDecorator->getCollection('Book')->getFields();
        $expectedConditionTree = new ConditionTreeBranch('Or', [
            new ConditionTreeLeaf('id', Operators::EQUAL, hex2bin('30303030')),
            new ConditionTreeLeaf('id', Operators::IN, [hex2bin('30303030')]),
            new ConditionTreeLeaf('title', Operators::EQUAL, 'Foundation'),
            new ConditionTreeLeaf('title', Operators::LIKE, 'Found%'),
            new ConditionTreeLeaf('cover', Operators::EQUAL, '123'),
        ]);

        $decoratedBook = $datasourceDecorator->getCollection('Book');

        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('list')
            ->once()
            ->withArgs(function ($caller, $filter) use ($expectedConditionTree, $conditionTree) {
                foreach ($filter->getConditionTree()->getConditions() as $key => $child) {
                    if (is_resource($child->getValue())) {
                        return stream_get_contents($child->getValue()) === $expectedConditionTree->getConditions()[$key]->getValue();
                    }
                    if ($child->getValue() !== $expectedConditionTree->getConditions()[$key]->getValue()) {
                        return false;
                    }
                }

                return true;
            })
            ->andReturn([])
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->list($caller, $filter, $projection);
    })->with('caller');

    test('list from relations - query & record should be transformed', function (Caller $caller) {
        $conditionTree = new ConditionTreeLeaf('book:id', Operators::EQUAL, '30303030');
        $filter = new PaginatedFilter(conditionTree: $conditionTree);
        $projection = new Projection(['id', 'book:id', 'book:cover']);

        $datasourceDecorator = $this->bucket[0];
        $decoratedFavorite = $datasourceDecorator->getCollection('Favorite');
        $records = [
            [
                'id'   => 2,
                'book' => [],
            ],
        ];
        $childCollection = $this->invokeProperty($decoratedFavorite, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->shouldReceive('list')
            ->andReturn($records)
            ->getMock();

        $this->invokeProperty($decoratedFavorite, 'childCollection', $mock);
        $decoratedFavorite->getFields();

        expect($decoratedFavorite->list($caller, $filter, $projection))->toEqual($records);
    })->with('caller');

    test('simple creation - record should be transformed when going to database', function (Caller $caller) {
        $record = [
            'id'    => '3030',
            'cover' => 'data:application/octet-stream;base64,aGVsbG8=',
        ];

        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('create')
            ->once()
            ->withArgs(function ($caller, $records) {
                if (is_resource($records['id'])) {
                    return stream_get_contents($records['id']) === hex2bin('3030');
                }
                if (is_resource($records['cover'])) {
                    return stream_get_contents($records['cover']) === 'hello';
                }

                return true;
            })
            ->andReturn([])
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->create($caller, $record);
    })->with('caller');

    test('simple creation - record should be transformed when coming from database', function (Caller $caller) {
        $record = [
            'id'    => hex2bin('3030'),
            'cover' => 'hello',
        ];

        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('create')
            ->once()
            ->withArgs(function ($caller, $records) {
                if (is_resource($records['id'])) {
                    return stream_get_contents($records['id']) === hex2bin('3030');
                }
                if (is_resource($records['cover'])) {
                    return stream_get_contents($records['cover']) === 'hello';
                }

                return true;
            })
            ->andReturn([])
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->create($caller, $record);
    })->with('caller');

    test('simple update - patch should be transformed when coming from database', function (Caller $caller) {
        $filter = new Filter();
        $patch = ['cover' => 'hello'];

        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('update')
            ->once()
            ->withArgs(function ($caller, $filter, $patch) {
                foreach ($patch as $key => $value) {
                    if (is_resource($value)) {
                        return stream_get_contents($value) === 'hello';
                    }
                }

                return true;
            })
            ->andReturn([])
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->update($caller, $filter, $patch);
    })->with('caller');

    test('aggregation with binary groups - groups in result should be transformed', function (Caller $caller) {
        $filter = new Filter();
        $aggregation = new Aggregation(field: 'title', operation: 'Count', groups: [['field' => 'cover']]);
        $datasourceDecorator = $this->bucket[0];
        $decoratedBook = $datasourceDecorator->getCollection('Book');

        $result = [
            [
                'value' => 1,
                'group' => [
                    'cover' => 'data:application/octet-stream;base64,aGVsbG8=',
                ],
            ],
        ];
        $childCollection = $this->invokeProperty($decoratedBook, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('aggregate')
            ->once()
            ->withArgs(function ($caller, $filter, $aggregation) {
                foreach ($aggregation->getGroups() as $group) {
                    if (is_resource($group['field'])) {
                        return stream_get_contents($group['field']) === 'cover';
                    }
                }

                return true;
            })
            ->andReturn($result)
            ->getMock();

        $this->invokeProperty($decoratedBook, 'childCollection', $mock);
        $decoratedBook->aggregate($caller, $filter, $aggregation);
    })->with('caller');

    test('aggregation from a relation - groups in result should be transformed', function (Caller $caller) {
        $filter = new Filter();
        $aggregation = new Aggregation(field: 'id', operation: 'Count', groups: [['field' => 'book:cover']]);
        $datasourceDecorator = $this->bucket[0];
        $decoratedFavorite = $datasourceDecorator->getCollection('Favorite');

        $result = [
            [
                'value' => 1,
                'group' => [
                    'book:cover' => 'data:application/octet-stream;base64,aGVsbG8=',
                ],
            ],
        ];

        $childCollection = $this->invokeProperty($decoratedFavorite, 'childCollection');
        $mock = mock($childCollection)
            ->makePartial()
            ->expects('aggregate')
            ->once()
            ->withArgs(function ($caller, $filter, $aggregation) {
                foreach ($aggregation->getGroups() as $group) {
                    if (is_resource($group['field'])) {
                        return stream_get_contents($group['field']) === 'book:cover';
                    }
                }

                return true;
            })
            ->andReturn($result)
            ->getMock();

        $this->invokeProperty($decoratedFavorite, 'childCollection', $mock);
        $decoratedFavorite->aggregate($caller, $filter, $aggregation);
    })->with('caller');
});
