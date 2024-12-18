<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\LazyJoin\LazyJoinCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;

beforeEach(
    function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'           => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'title'        => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL, Operators::IN]),
                'author_id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER),
                'author'       => new ManyToOneSchema(
                    foreignKey: 'author_id',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
            ]
        );

        $collectionPerson = new Collection($datasource, 'Person');
        $collectionPerson->addFields(
            [
                'id'                => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'first_name'        => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL, Operators::IN]),
                'books'             => new OneToManySchema(
                    originKey: 'author_id',
                    originKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
            ]
        );

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionPerson);

        $datasourceDecorator = new DatasourceDecorator($datasource, LazyJoinCollection::class);

        $this->buildAgent($datasource);

        $this->bucket = [$datasourceDecorator];
    }
);

describe('LazyJoin collection', function () {
    describe('when call list', function () {
        it('not join when projection ask for target field only', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($projection)->toEqual(new Projection(['id', 'author_id']));

                    return true;
                })
                ->andReturn([[ 'id' => 1, 'author_id' => 2 ], [ 'id' => 2, 'author_id' => 5 ]])
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $records = $decoratedCollectionBook->list(
                $caller,
                new PaginatedFilter(),
                new Projection(['id', 'author:id'])
            );

            expect($records)->toEqual(
                [[ 'id' => 1, 'author' => [ 'id' => 2 ]], [ 'id' => 2, 'author' => [ 'id' => 5 ]]]
            );
        })->with('caller');

        it('join when projection ask for multiple fields in foreign collection', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($projection)->toEqual(new Projection(['id', 'author:id', 'author:first_name']));

                    return true;
                })
                ->andReturn(
                    [
                        [ 'id' => 1, 'author' => ['id' => 2, 'first_name' => 'Isaac']],
                        ['id' => 2, 'author' => ['id' => 5, 'first_name' => 'J.K']],
                    ]
                )
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $records = $decoratedCollectionBook->list(
                $caller,
                new PaginatedFilter(),
                new Projection(['id', 'author:id', 'author:first_name'])
            );

            expect($records)->toEqual(
                [
                    ['id' => 1, 'author' => ['id' => 2, 'first_name' => 'Isaac']],
                    ['id' => 2, 'author' => ['id' => 5, 'first_name' => 'J.K']],
                ]
            );
        })->with('caller');

        it('not join when when condition tree is on foreign key target', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($filter->getConditionTree())->toEqual(
                        new ConditionTreeLeaf(field: 'author_id', operator: Operators::IN, value: [2, 5])
                    )->and($projection)->toEqual(new Projection(['id', 'author_id']));

                    return true;
                })
                ->andReturn(
                    [['id' => 1, 'author_id' => 2], ['id' => 2, 'author_id' => 5], ['id' => 3, 'author_id' => 5]]
                )
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $filter = new PaginatedFilter(conditionTree: new ConditionTreeLeaf(field: 'author:id', operator: Operators::IN, value: [2, 5]));
            $records = $decoratedCollectionBook->list(
                $caller,
                $filter,
                new Projection(['id', 'author:id'])
            );

            expect($records)->toEqual(
                [
                    ['id' => 1, 'author' => ['id' => 2]],
                    ['id' => 2, 'author' => ['id' => 5]],
                    ['id' => 3, 'author' => ['id' => 5]],
                ]
            );
        })->with('caller');

        it('join when when condition tree is on foreign key collection_field', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($filter->getConditionTree())->toEqual(
                        new ConditionTreeLeaf(field: 'author:first_name', operator: Operators::IN, value: ['J.K', 'Isaac'])
                    )->and($projection)->toEqual(new Projection(['id', 'author:id', 'author:first_name']));

                    return true;
                })
                ->andReturn(
                    [
                        [ 'id' => 1, 'author' => ['id' => 2, 'first_name' => 'Isaac']],
                        ['id' => 2, 'author' => ['id' => 5, 'first_name' => 'J.K']],
                        ['id' => 3, 'author' => ['id' => 5, 'first_name' => 'J.K']],
                    ]
                )
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $filter = new PaginatedFilter(conditionTree: new ConditionTreeLeaf(field: 'author:first_name', operator: Operators::IN, value: ['J.K', 'Isaac']));
            $records = $decoratedCollectionBook->list(
                $caller,
                $filter,
                new Projection(['id', 'author:id', 'author:first_name'])
            );

            expect($records)->toEqual(
                [
                    [ 'id' => 1, 'author' => ['id' => 2, 'first_name' => 'Isaac']],
                    ['id' => 2, 'author' => ['id' => 5, 'first_name' => 'J.K']],
                    ['id' => 3, 'author' => ['id' => 5, 'first_name' => 'J.K']],
                ]
            );
        })->with('caller');

        it('disable join on condition tree but not in projection', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($filter->getConditionTree())->toEqual(
                        new ConditionTreeLeaf(field: 'author_id', operator: Operators::IN, value: [2, 5])
                    )->and($projection)->toEqual(new Projection(['id', 'author:first_name']));

                    return true;
                })
                ->andReturn(
                    [
                        [ 'id' => 1, 'author' => ['first_name' => 'Isaac']],
                        ['id' => 2, 'author' => ['first_name' => 'J.K']],
                        ['id' => 3, 'author' => ['first_name' => 'J.K']],
                    ]
                )
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $filter = new PaginatedFilter(conditionTree: new ConditionTreeLeaf(field: 'author:id', operator: Operators::IN, value: [2, 5]));
            $records = $decoratedCollectionBook->list(
                $caller,
                $filter,
                new Projection(['id', 'author:first_name'])
            );

            expect($records)->toEqual(
                [
                    [ 'id' => 1, 'author' => ['first_name' => 'Isaac']],
                    ['id' => 2, 'author' => ['first_name' => 'J.K']],
                    ['id' => 3, 'author' => ['first_name' => 'J.K']],
                ]
            );
        })->with('caller');

        it('disable join on projection but not on condition tree', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($filter->getConditionTree())->toEqual(
                        new ConditionTreeLeaf(field: 'author:first_name', operator: Operators::IN, value: ['J.K', 'Isaac'])
                    )->and($projection)->toEqual(new Projection(['id', 'author_id']));

                    return true;
                })
                ->andReturn(
                    [
                        [ 'id' => 1, 'author_id' => 2],
                        ['id' => 2, 'author_id' => 5],
                        ['id' => 3, 'author_id' => 5],
                    ]
                )
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $filter = new PaginatedFilter(conditionTree: new ConditionTreeLeaf(field: 'author:first_name', operator: Operators::IN, value: ['J.K', 'Isaac']));
            $records = $decoratedCollectionBook->list(
                $caller,
                $filter,
                new Projection(['id', 'author:id'])
            );

            expect($records)->toEqual(
                [
                    [ 'id' => 1, 'author' => [ 'id' => 2 ]],
                    [ 'id' => 2, 'author' => [ 'id' => 5 ]],
                    [ 'id' => 3, 'author' => [ 'id' => 5 ]],
                ]
            );
        })->with('caller');

        it('correctly handle null relations', closure: function (Caller $caller) {
            [$datasourceDecorator] = $this->bucket;

            /** @var LazyJoinCollection $decoratedCollectionBook */
            $decoratedCollectionBook = $datasourceDecorator->getCollection('Book');
            $childCollection = $this->invokeProperty($decoratedCollectionBook, 'childCollection');
            $mock = \Mockery::mock($childCollection)
                ->makePartial()
                ->expects('list')
                ->once()
                ->withArgs(function ($caller, $filter, $projection) {
                    expect($projection)->toEqual(new Projection(['id', 'author_id']));

                    return true;
                })
                ->andReturn([[ 'id' => 1, 'author_id' => 2 ], [ 'id' => 2, 'author_id' => null ]])
                ->getMock();
            $this->invokeProperty($decoratedCollectionBook, 'childCollection', $mock);

            $records = $decoratedCollectionBook->list(
                $caller,
                new PaginatedFilter(),
                new Projection(['id', 'author:id'])
            );

            expect($records)->toEqual(
                [[ 'id' => 1, 'author' => [ 'id' => 2 ]], [ 'id' => 2, 'author' => null]]
            );
        })->with('caller');
    });
});
