<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameField\RenameFieldCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use Mockery\Mock;

describe('RenameFieldCollection', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'authorId'   => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'title'      => new ColumnSchema(columnType: PrimitiveType::STRING),
                'bookPerson' => new OneToManySchema(
                    originKey: 'bookId',
                    originKeyTarget: 'id',
                    foreignCollection: 'BookPerson',
                ),
                'persons'   => new ManyToManySchema(
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
                'person'   => new ManyToOneSchema(
                    foreignKey: 'personId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'book'   => new ManyToOneSchema(
                    foreignKey: 'bookId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
                'date'     => new ColumnSchema(columnType: PrimitiveType::DATE),
            ]
        );

        $collectionPerson = new Collection($datasource, 'Person');
        $collectionPerson->addFields(
            [
                'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'firstName'  => new ColumnSchema(columnType: PrimitiveType::STRING),
                'lastName'   => new ColumnSchema(columnType: PrimitiveType::STRING),
                'bookPerson' => new OneToOneSchema(
                    originKey: 'personId',
                    originKeyTarget: 'id',
                    foreignCollection: 'BookPerson',
                ),
            ]
        );

        $collectionBook = \Mockery::mock($collectionBook)->makePartial();
        $collectionPerson = \Mockery::mock($collectionPerson)->makePartial();
        $collectionBookPerson = \Mockery::mock($collectionBookPerson)->makePartial();

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionBookPerson);
        $datasource->addCollection($collectionPerson);
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, RenameFieldCollection::class);

        $this->bucket = compact('datasource', 'datasourceDecorator', 'collectionBook', 'collectionPerson', 'collectionBookPerson');
    });

    test('renameField() should throw when renaming a field which does not exists', closure: function () {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var RenameFieldCollection $computedCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');

        expect(
            static fn () => $renameFieldCollection->renameField('unknown', 'newTitle')
        )->toThrow(ForestException::class, "🌳🌳🌳 No such field 'unknown'");
    });

    test('renameField() should throw when renaming a field using an older name', closure: function () {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var RenameFieldCollection $computedCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->renameField('id', 'key');

        expect(
            static fn () => $renameFieldCollection->renameField('id', 'primaryKey')
        )->toThrow(ForestException::class, "🌳🌳🌳 No such field 'id'");
    });

    test('renameField() should allow renaming multiple times the same field', closure: function () {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        /** @var RenameFieldCollection $computedCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->renameField('id', 'key');
        $renameFieldCollection->renameField('key', 'primaryKey');
        $renameFieldCollection->renameField('primaryKey', 'primaryId');
        $renameFieldCollection->renameField('primaryId', 'id');

        expect($collectionBook->getFields())->toEqual($renameFieldCollection->getFields());
    });

    test('create() should act as a pass-through when not renaming anything', closure: function (Caller $caller) {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $record = [
            'id'       => 1,
            'authorId' => 1,
            'title'    => 'Foundation',
        ];
        $collectionBook->shouldReceive('create')->andReturn($record);

        /** @var RenameFieldCollection $computedCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');

        expect($renameFieldCollection->create($caller, ['authorId' => 1, 'title'    => 'Foundation']))->toEqual($record);
    })->with('caller');

    test('list() should act as a pass-through when not renaming anything', closure: function (Caller $caller) {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $records = [
            ['id' => 1, 'bookPerson' => ['date' => 'something']],
        ];
        $collectionBook->shouldReceive('list')->andReturn($records);
        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $projection = new Projection(['id', 'bookPerson:date']);

        expect($renameFieldCollection->list($caller, new PaginatedFilter(), $projection))->toEqual($records);
    })->with('caller');

    test('update() should act as a pass-through when not renaming anything', closure: function (Caller $caller) {
        /** @var Mock $collectionBook */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $filter = new Filter();
        $collectionBook->expects('update')->withArgs(function ($caller, Filter $refineFilter, $patch) use ($filter) {
            return $filter->toArray() === $refineFilter->toArray();
        });

        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->update($caller, $filter, ['id' => 1, 'title' => 'Foundation']);
    })->with('caller');

    test('aggregate() should act as a pass-through when not renaming anything', closure: function (Caller $caller) {
        /** @var Mock $collectionBook */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $result = [['value' => 34, 'group' => []]];
        $aggregate = new Aggregation('Count');
        $filter = new Filter();
        $collectionBook->expects('aggregate')->andReturn($result);

        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        expect($renameFieldCollection->aggregate($caller, $filter, $aggregate))->toEqual($result);
    })->with('caller');

    test('create() should rewrite the record when renaming columns and relations', closure: function (Caller $caller) {
        /** @var Mock $collectionBook */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $collectionBook->shouldReceive('create')
            ->andReturn([
                'id'       => 1,
                'authorId' => 1,
                'title'    => 'Foo',
            ]);

        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->renameField('id', 'primaryKey');

        expect($renameFieldCollection->create($caller, ['primaryKey' => 1, 'title' => 'Foo']))
            ->toEqual([
                'primaryKey'       => 1,
                'authorId'         => 1,
                'title'            => 'Foo',
            ]);
    })->with('caller');

    test('list() should rewrite the filter, projection and record when renaming columns and relations', closure: function (Caller $caller) {
        /** @var Mock $collectionPerson */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionPerson = $this->bucket['collectionPerson'];

        /** @var RenameFieldCollection $personCollection */
        $renameFieldCollectionPerson = $datasourceDecorator->getCollection('Person');
        $renameFieldCollectionPerson->renameField('id', 'primaryKey');
        $renameFieldCollectionPerson->renameField('bookPerson', 'myNovelAuthor');

        $bookPersonCollectionBookPerson = $datasourceDecorator->getCollection('BookPerson');
        $bookPersonCollectionBookPerson->renameField('date', 'createdAt');

        $projection = new Projection(['primaryKey', 'myNovelAuthor:createdAt']);
        $filter = new PaginatedFilter(
            conditionTree: new ConditionTreeBranch(
                'And',
                [
                    new ConditionTreeLeaf('primaryKey', Operators::NOT_EQUAL, 0),
                    new ConditionTreeLeaf('myNovelAuthor:createdAt', Operators::NOT_EQUAL, 0),
                ]
            ),
            sort: new Sort(
                [
                    ['field' => 'primaryKey', 'ascending' => false],
                    ['field' => 'myNovelAuthor:createdAt', 'ascending' => true],
                ]
            ),
        );
        $collectionPerson->shouldReceive('list')
            ->once()
            ->withArgs(function (Caller $caller, PaginatedFilter $receivedFilter, Projection $receivedProjection) {
                $expectedSort = [
                    ['field' => 'id', 'ascending' => false],
                    ['field' => 'bookPerson:date', 'ascending' => true],
                ];
                $expectedProjection = ['id', 'bookPerson:date'];
                expect($receivedFilter->getSort()->toArray())->toEqual($expectedSort)
                    ->and($receivedProjection->toArray())->toEqual($expectedProjection);

                return true;
            })
            ->andReturn([['id' => 1, 'bookPerson' => ['date' => 'something']]]);

        expect($renameFieldCollectionPerson->list($caller, $filter, $projection))->toEqual([
            ['myNovelAuthor' => ['createdAt' => 'something'], 'primaryKey' => 1],
        ]);
    })->with('caller');

    test('list() should rewrite the record with null relation when renaming columns and relations', closure: function (Caller $caller) {
        /** @var Mock $collectionBook */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $collectionBook->shouldReceive('list')
            ->andReturn(
                [['id' => 1, 'bookPerson' => null, 'title' => 'Foundation']]
            );
        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->renameField('id', 'primaryKey');
        $renameFieldCollection->renameField('bookPerson', 'myNovelAuthor');
        $projection = new Projection(['id', 'myNovelAuthor:firstName', 'title']);
        $filter = new PaginatedFilter(
            sort: new Sort(
                [
                    ['field' => 'primaryKey', 'ascending' => false],
                    ['field' => 'myNovelAuthor:firstName', 'ascending' => true],
                ]
            ),
        );

        expect($renameFieldCollection->list($caller, $filter, $projection))
            ->toEqual(
                [['myNovelAuthor' => null, 'title' => 'Foundation', 'primaryKey' => 1,]]
            );
    })->with('caller');

    test('update() should rewrite the filter and patch when not renaming anything', closure: function (Caller $caller) {
        /** @var Mock $collectionBook */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $filter = new Filter(conditionTree: new ConditionTreeLeaf('primaryKey', Operators::EQUAL, 1));
        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->renameField('id', 'primaryKey');
        $collectionBook->expects('update')->withArgs(function ($caller, $filter, $patch) {
            return $filter->getConditionTree()->getField() === 'id' && isset($patch['id'], $patch['title']) && count($patch) === 2;
        });

        $renameFieldCollection->update($caller, $filter, ['primaryKey' => 1, 'title' => 'Foundation']);
    })->with('caller');

    test('delete() should rewrite the filter', closure: function (Caller $caller) {
        /** @var Mock $collectionBook */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionBook = $this->bucket['collectionBook'];
        $filter = new Filter(conditionTree: new ConditionTreeLeaf('primaryKey', Operators::EQUAL, 1));
        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('Book');
        $renameFieldCollection->renameField('id', 'primaryKey');
        $collectionBook->expects('delete')->withArgs(function ($caller, $filter) {
            return $filter->getConditionTree()->getField() === 'id';
        });

        $renameFieldCollection->delete($caller, $filter);
    })->with('caller');

    test('aggregate() should rewrite the filter and the result when renaming columns and relations', closure: function (Caller $caller) {
        /** @var Mock $collectionPerson */
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        $collectionPerson = $this->bucket['collectionPerson'];

        /** @var RenameFieldCollection $renameFieldCollectionPerson */
        $renameFieldCollectionPerson = $datasourceDecorator->getCollection('Person');
        $renameFieldCollectionPerson->renameField('id', 'primaryKey');
        $renameFieldCollectionPerson->renameField('bookPerson', 'myNovelAuthor');
        $renameFieldCollectionBookPerson = $datasourceDecorator->getCollection('BookPerson');
        $renameFieldCollectionBookPerson->renameField('date', 'createdAt');
        $filter = new Filter(
            conditionTree: new ConditionTreeLeaf('myNovelAuthor:createdAt', Operators::NOT_EQUAL, 'abc'),
        );
        $aggregation = new Aggregation('Count', 'id', [['field' => 'myNovelAuthor:createdAt']]);

        $collectionPerson->expects('aggregate')->withArgs(function (Caller $caller, Filter $filter, Aggregation $aggregation) {
            $expectedConditionTree = new ConditionTreeLeaf('bookPerson:date', Operators::NOT_EQUAL, 'abc');
            $expectedAggregation = new Aggregation('Count', 'id', [['field' => 'bookPerson:date', 'operation' => null]]);

            expect($filter->getConditionTree()->toArray())->toEqual($expectedConditionTree->toArray())
                ->and($aggregation->toArray())->toEqual($expectedAggregation->toArray());

            return true;

        })->andReturn([['value' => 34, 'group' => ['bookPerson:createdAt' => 'foo']]]);

        expect($renameFieldCollectionPerson->aggregate($caller, $filter, $aggregation))
            ->toEqual([['value' => 34, 'group' => ['myNovelAuthor:createdAt' => 'foo']]]);
    })->with('caller');

    test('the columns should be renamed in the schema when renaming foreign keys', closure: function () {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('BookPerson');
        $renameFieldCollection->renameField('bookId', 'novelId');
        $renameFieldCollection->renameField('personId', 'authorId');
        $fields = $renameFieldCollection->getFields();

        expect($fields['authorId'])->toBeInstanceOf(ColumnSchema::class)
            ->and($fields['novelId'])->toBeInstanceOf(ColumnSchema::class)
            ->and($fields)->not()->toHaveKeys(['bookId', 'personId']);
    });

    test('the relations should be updated in all collections when renaming foreign keys', closure: function () {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var RenameFieldCollection $renameFieldCollection */
        $renameFieldCollection = $datasourceDecorator->getCollection('BookPerson');
        $renameFieldCollection->renameField('bookId', 'novelId');
        $renameFieldCollection->renameField('personId', 'authorId');
        $bookPersonFields = $renameFieldCollection->getFields();
        $bookFields = $datasourceDecorator->getCollection('Book')->getFields();
        $personFields = $datasourceDecorator->getCollection('Person')->getFields();

        expect($bookFields['persons']->getForeignKey())->toEqual('authorId')
            ->and($bookFields['persons']->getOriginKey())->toEqual('novelId')
            ->and($bookPersonFields['book']->getForeignKey())->toEqual('novelId')
            ->and($bookPersonFields['person']->getForeignKey())->toEqual('authorId')
            ->and($personFields['bookPerson']->getOriginKey())->toEqual('authorId');
    });

    test('when renaming primary keys should update all collections', function () {
        $datasourceDecorator = $this->bucket['datasourceDecorator'];
        /** @var RenameFieldCollection $renameFieldCollectionBook */
        $renameFieldCollectionBook = $datasourceDecorator->getCollection('Book');
        $renameFieldCollectionBook->renameField('id', 'newBookId');
        /** @var RenameFieldCollection $renameFieldCollectionPerson */
        $renameFieldCollectionPerson = $datasourceDecorator->getCollection('Person');
        $renameFieldCollectionPerson->renameField('id', 'newPersonId');

        $bookFields = $renameFieldCollectionBook->getFields();
        $personFields = $renameFieldCollectionPerson->getFields();
        $bookPersonFields = $datasourceDecorator->getCollection('BookPerson')->getFields();

        expect($bookFields['persons']->getOriginKeyTarget())->toEqual('newBookId');
        expect($bookFields['bookPerson']->getOriginKeyTarget())->toEqual('newBookId');
        expect($bookPersonFields['book']->getForeignKeyTarget())->toEqual('newBookId');
        expect($bookPersonFields['person']->getForeignKeyTarget())->toEqual('newPersonId');
        expect($personFields['bookPerson']->getOriginKeyTarget())->toEqual('newPersonId');
    });
});
