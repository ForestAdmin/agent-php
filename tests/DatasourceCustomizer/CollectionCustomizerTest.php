<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Context\CollectionCustomizationContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\HookCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection\PublicationCollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\Rules;
use ForestAdmin\AgentPHP\Tests\TestCase;

describe('CollectionCustomizer', function () {
    $before = static function (TestCase $testCase, $collectionName = 'Book', $operators = [Operators::EQUAL, Operators::IN]) {
        $datasource = new Datasource();
        $collectionBook = new Collection($datasource, 'Book');
        $collectionBook->addFields(
            [
                'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, filterOperators: $operators),
                'title'           => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL]),
                'reference'       => new ColumnSchema(columnType: PrimitiveType::STRING),
                'childId'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN]),
                'authorId'        => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
                'author'          => new ManyToOneSchema(
                    foreignKey: 'authorId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Person',
                ),
                'persons'         => new ManyToManySchema(
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
                'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
                'personId' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'bookId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'category' => new ManyToOneSchema(
                    foreignKey: 'categoryId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Category',
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
                'id'             => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
                'name'           => new ColumnSchema(columnType: PrimitiveType::STRING),
                'nameInReadOnly' => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true),
                'book'           => new OneToOneSchema(
                    originKey: 'authorId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
                'books'          => new ManyToManySchema(
                    originKey: 'personId',
                    originKeyTarget: 'id',
                    foreignKey: 'bookId',
                    foreignKeyTarget: 'id',
                    foreignCollection: 'Book',
                    throughCollection: 'BookPerson',
                ),
            ]
        );

        $collectionCategory = new Collection($datasource, 'Category');
        $collectionCategory->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
                'label' => new ColumnSchema(columnType: PrimitiveType::STRING),
                'books' => new OneToManySchema(
                    originKey: 'categoryId',
                    originKeyTarget: 'id',
                    foreignCollection: 'Book',
                ),
            ]
        );

        $datasource->addCollection($collectionBook);
        $datasource->addCollection($collectionBookPerson);
        $datasource->addCollection($collectionPerson);
        $datasource->addCollection($collectionCategory);
        $testCase->buildAgent($datasource);

        $datasourceCustomizer = new DatasourceCustomizer();
        $datasourceCustomizer->addDatasource($datasource);

        $customizer = new CollectionCustomizer($datasourceCustomizer, $datasourceCustomizer->getStack(), $collectionName);

        $testCase->bucket = [$customizer, $datasourceCustomizer];
    };

    test('addField() should add a field to early collection', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $data = [['id' => 1, 'title' => 'Foundation'], ['id' => 2, 'title' => 'Harry Potter']];

        $stack = $datasourceCustomizer->getStack();
        $earlyComputed = $stack->earlyComputed;
        $earlyComputed = \Mockery::mock($earlyComputed)
            ->shouldReceive('getCollection')
            ->andReturn($datasourceCustomizer->getStack()->earlyComputed->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'earlyComputed', $earlyComputed);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $fieldDefinition = new ComputedDefinition(
            PrimitiveType::STRING,
            ['title'],
            fn ($records) => collect($records)->map(fn ($record) => $record['title'] . '-2022'),
            true
        );

        $customizer->addField('test', $fieldDefinition);
        $datasourceCustomizer->getDatasource();
        \Mockery::close();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->earlyComputed->getCollection('Book');

        expect($computedCollection->getFields())
            ->toHaveKey('test')
            ->and($computedCollection->getComputed('test')->getValues($data))
            ->toEqual(collect(['Foundation-2022', 'Harry Potter-2022']));
    });

    test('addField() should add a field to late collection', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $data = [['id' => 1, 'title' => 'Foundation'], ['id' => 2, 'title' => 'Harry Potter']];

        $stack = $datasourceCustomizer->getStack();
        $lateComputed = $stack->lateComputed;
        $lateComputed = \Mockery::mock($lateComputed)
            ->shouldReceive('getCollection')
            ->once()
            ->andReturn($datasourceCustomizer->getStack()->lateComputed->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'lateComputed', $lateComputed);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $fieldDefinition = new ComputedDefinition(
            PrimitiveType::STRING,
            ['id'],
            fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
            false
        );

        $customizer->addManyToOneRelation('mySelf', 'Book', 'id', 'childId');
        $customizer->addField('mySelf', $fieldDefinition);
        $datasourceCustomizer->getDatasource();
        \Mockery::close();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

        expect($computedCollection->getFields())
            ->toHaveKey('mySelf')
            ->and($computedCollection->getComputed('mySelf')->getValues($data))
            ->toEqual(collect(['1-Foo', '2-Foo']));
    });

    test('relations addManyToOneRelation() should add a many to one', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->relation->getCollection('Book');
        $bookRelation = \Mockery::mock($book)
            ->shouldReceive('addRelation')
            ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Book'))
            ->getMock();
        $collections = $stack->relation->getCollections();
        $collections->put('Book', $bookRelation);

        $this->invokeProperty($stack->relation, 'collections', $collections);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $fieldDefinition = new ComputedDefinition(
            PrimitiveType::STRING,
            ['id'],
            fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
            false
        );

        $customizer->addManyToOneRelation('mySelf', 'Book', 'id', 'childId');
        $customizer->addField('mySelf', $fieldDefinition);
        $datasourceCustomizer->getDatasource();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

        expect($computedCollection->getFields())->toHaveKey('mySelf');
    });

    test('relations addOneToOneRelation() should add a one to one', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->relation->getCollection('Book');
        $bookRelation = \Mockery::mock($book)
            ->shouldReceive('addRelation')
            ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Book'))
            ->getMock();
        $collections = $stack->relation->getCollections();
        $collections->put('Book', $bookRelation);

        $this->invokeProperty($stack->relation, 'collections', $collections);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $fieldDefinition = new ComputedDefinition(
            PrimitiveType::STRING,
            ['id'],
            fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
            false
        );

        $customizer->addOneToOneRelation('newRelation', 'Person', 'id', 'childId');
        $customizer->addField('newRelation', $fieldDefinition);
        $datasourceCustomizer->getDatasource();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

        expect($computedCollection->getFields())->toHaveKey('newRelation');
    });

    test('relations addOneToManyRelation() should add a one to many', function () use ($before) {
        $before($this, 'Category');
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $category = $stack->relation->getCollection('Category');
        $categoryRelation = \Mockery::mock($category)
            ->shouldReceive('addRelation')
            ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Category'))
            ->getMock();
        $collections = $stack->relation->getCollections();
        $collections->put('Category', $categoryRelation);

        $this->invokeProperty($stack->relation, 'collections', $collections);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $fieldDefinition = new ComputedDefinition(
            PrimitiveType::STRING,
            ['id'],
            fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
            false
        );

        $customizer->addOneToManyRelation('newRelation', 'Person', 'id', 'id');
        $customizer->addField('newRelation', $fieldDefinition);
        $datasourceCustomizer->getDatasource();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Category');

        expect($computedCollection->getFields())->toHaveKey('newRelation');
    });

    test('relations addManyToManyRelation() should add a many to many', function () use ($before) {
        $before($this, 'Person');
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $person = $stack->relation->getCollection('Person');
        $personRelation = \Mockery::mock($person)
            ->shouldReceive('addRelation')
            ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Person'))
            ->getMock();
        $collections = $stack->relation->getCollections();
        $collections->put('Person', $personRelation);

        $this->invokeProperty($stack->relation, 'collections', $collections);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $fieldDefinition = new ComputedDefinition(
            PrimitiveType::STRING,
            ['id'],
            fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
            false
        );

        $customizer->addManyToManyRelation('newRelation', 'Person', 'BookPerson', 'id', 'id');
        $customizer->addField('newRelation', $fieldDefinition);
        $datasourceCustomizer->getDatasource();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Person');

        expect($computedCollection->getFields())->toHaveKey('newRelation');
    });

    test('addSegment() should add a segment', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->segment->getCollection('Book');

        $customizer->addSegment('newSegment', fn () => [
            'field'    => 'id',
            'operator' => Operators::GREATER_THAN,
            'value'    => 1,
        ]);
        $datasourceCustomizer->getDatasource();

        expect($book->getSegments()->toArray())->toEqual(['newSegment']);
    });

    test('emulateFieldSorting() should emulate sort on field', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->sort->getCollection('Book');

        $customizer->emulateFieldSorting('title');
        $datasourceCustomizer->getDatasource();

        expect($this->invokeProperty($book, 'sorts'))->toHaveKey('title');
    });

    test('replaceFieldSorting() should replace sort on field', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->sort->getCollection('Book');

        $customizer->replaceFieldSorting('title', [['field' => 'title', 'ascending' => true]]);
        $datasourceCustomizer->getDatasource();

        $sort = $this->invokeProperty($book, 'sorts');
        expect($sort)
            ->toHaveKey('title')
            ->and($sort['title'])
            ->toEqual(new Sort([['field' => 'title', 'ascending' => true]]));
    });

    test('replaceSearch() should call the search decorator', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $search = $stack->search;
        $search = \Mockery::mock($search)
            ->shouldReceive('getCollection')
            ->once()
            ->andReturn($datasourceCustomizer->getStack()->search->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'search', $search);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $condition = fn ($search) => [
            ['field' => 'title', 'operator' => Operators::EQUAL, 'value' => $search],
        ];
        $customizer->replaceSearch($condition);
        $datasourceCustomizer->getDatasource();

        \Mockery::close();

        /** @var $searchCollection $searchCollection */
        $searchCollection = $datasourceCustomizer->getStack()->search->getCollection('Book');

        expect($this->invokeProperty($searchCollection, 'replacer'))->toEqual($condition);
    });

    test('disableCount() should disable count on the collection', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->schema->getCollection('Book');

        $customizer->disableCount();
        $datasourceCustomizer->getDatasource();

        expect($book->isCountable())->toBeFalse();
    });

    test('addChart() should add a chart', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $book = $stack->chart->getCollection('Book');
        $customizer->addChart('newChart', fn ($context, $resultBuilder) => $resultBuilder->value(34));
        $datasourceCustomizer->getDatasource();

        expect($book->getCharts()->toArray())->toEqual(['newChart']);
    });

    test('replaceFieldWriting() should update the field behavior of the collection', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;

        $stack = $datasourceCustomizer->getStack();
        $write = $stack->write;
        $write = \Mockery::mock($write)
            ->shouldReceive('getCollection')
            ->once()
            ->andReturn($datasourceCustomizer->getStack()->write->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'write', $write);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $field = 'title';
        $condition = function ($value, $context) {
            $reference = $value . '-2023';
            $title = $value;

            return $context->getCollection()->update(
                new Filter(new ConditionTreeLeaf('id', Operators::EQUAL, $context->getRecord()['id'])),
                compact('reference', 'title')
            );
        };
        $customizer->replaceFieldWriting($field, $condition);
        $datasourceCustomizer->getDatasource();
        \Mockery::close();

        /** @var WriteReplaceCollection $writeReplaceCollection */
        $writeReplaceCollection = $datasourceCustomizer->getStack()->write->getCollection('Book');

        expect($this->invokeProperty($writeReplaceCollection, 'handlers'))->toEqual([$field => $condition]);
    });

    test('renameField() should rename a field in collection', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->renameField('title', 'newTitle');
        $datasourceCustomizer->getDatasource();
        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->renameField->getCollection('Book');

        expect($computedCollection->getFields())
            ->toHaveKey('newTitle')
            ->not->toHaveKey('title');
    });

    test('removeField() should remove a field in collection', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->removeField('title');
        $datasourceCustomizer->getDatasource();
        /** @var PublicationCollectionDecorator $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->publication->getCollection('Book');

        expect($computedCollection->getFields())->not->toHaveKey('title');
    });

    test('addExternalRelation() should call addField', function (Caller $caller) use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $data = [['id' => 1, 'title' => 'Dune']];

        $stack = $datasourceCustomizer->getStack();

        $lateComputed = $stack->lateComputed;
        $lateComputed = \Mockery::mock($lateComputed)
            ->shouldReceive('getCollection')
            ->once()
            ->andReturn($datasourceCustomizer->getStack()->lateComputed->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'lateComputed', $lateComputed);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $customizer->addExternalRelation(
            'tags',
            [
                'schema'      => ['etag' => 'String', 'selfLink' => 'String'],
                'listRecords' => fn () => [
                    ['etag' => 'OTD2tB19qn4', 'selfLink' => 'https://www.googleapis.com/books/v1/volumes/_ojXNuzgHRcC'],
                    ['etag' => 'NsxMT6kCCVs', 'selfLink' => 'https://www.googleapis.com/books/v1/volumes/RJxWIQOvoZUC'],
                ],
            ]
        );
        $datasourceCustomizer->getDatasource();

        \Mockery::close();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

        expect($computedCollection->getFields())->toHaveKey('tags')
            ->and($computedCollection->getComputed('tags')->getValues($data, new CollectionCustomizationContext($computedCollection, $caller)))->toEqual(collect([
                [
                    [
                        "etag"     => "OTD2tB19qn4",
                        "selfLink" => "https://www.googleapis.com/books/v1/volumes/_ojXNuzgHRcC",
                    ],
                    [
                        "etag"     => "NsxMT6kCCVs",
                        "selfLink" => "https://www.googleapis.com/books/v1/volumes/RJxWIQOvoZUC",
                    ],
                ],
            ]));
    })->with('caller');

    test('addExternalRelation() should thrown an exception when the plugin have options keys missing', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->addExternalRelation('tags', []);

        expect(fn () => $datasourceCustomizer->getDatasource())->toThrow(ForestException::class, '🌳🌳🌳 The options parameter must contains the following keys: `name, schema, listRecords`');
    });

    test('importField() should call addField', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $stack = $datasourceCustomizer->getStack();

        $lateComputed = $stack->lateComputed;
        $lateComputed = \Mockery::mock($lateComputed)
            ->shouldReceive('getCollection')
            ->once()
            ->andReturn($datasourceCustomizer->getStack()->lateComputed->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'lateComputed', $lateComputed);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);

        $customizer->importField(
            'titleCopy',
            [
                'path' => 'title',
            ]
        );
        $datasourceCustomizer->getDatasource();

        \Mockery::close();

        /** @var ComputedCollection $computedCollection */
        $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

        expect($computedCollection->getFields())->toHaveKey('titleCopy')
            ->and($computedCollection->getFields()['titleCopy'])->toBeInstanceOf(ColumnSchema::class);
    });

    test('importField() should throw when the operators of the pk does not have Equal or In', function () use ($before) {
        $before($this, 'Book', []);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $stack = $datasourceCustomizer->getStack();

        $lateComputed = $stack->lateComputed;
        $lateComputed = \Mockery::mock($lateComputed)
            ->shouldReceive('getCollection')
            ->once()
            ->andReturn($datasourceCustomizer->getStack()->lateComputed->getCollection('Book'))
            ->getMock();

        $this->invokeProperty($stack, 'lateComputed', $lateComputed);
        $this->invokeProperty($datasourceCustomizer, 'stack', $stack);
        $this->invokeProperty($customizer, 'stack', $stack);
        $customizer->importField('titleCopy', ['path' => 'title']);

        expect(fn () => $datasourceCustomizer->getDatasource())->toThrow(ForestException::class, "🌳🌳🌳 Cannot override operators on collection titleCopy: the primary key columns must support 'Equal' and 'In' operators");
        \Mockery::close();
    });

    test('importField() when the field is not writable should throw an exception', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->importField('authorName', ['path' => 'author:nameInReadOnly']);

        expect(fn () => $datasourceCustomizer->getDatasource())->toThrow(ForestException::class, '🌳🌳🌳 Readonly option should not be false because the field author:nameInReadOnly is not writable');
    });

    test('importField() when the "readOnly" option is false should throw an exception', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->importField(
            'authorName',
            [
                'path'     => 'author:nameInReadOnly',
                'readonly' => false,
            ]
        );

        expect(fn () => $datasourceCustomizer->getDatasource())->toThrow(ForestException::class, '🌳🌳🌳 Readonly option should not be false because the field author:nameInReadOnly is not writable');
    });

    test('importField() when the given field does not exist should throw an exception', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->importField('authorName', ['path'     => 'author:doesNotExistPath']);

        expect(fn () => $datasourceCustomizer->getDatasource())->toThrow(ForestException::class, '🌳🌳🌳 Field doesNotExistPath not found in collection Person');
    });

    test('importField() should thrown an exception when the plugin have options keys missing', function () use ($before) {
        $before($this);
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->importField('titleCopy', []);

        expect(fn () => $datasourceCustomizer->getDatasource())->toThrow(ForestException::class, '🌳🌳🌳 The options parameter must contains the following keys: `name, path`');
    });

    test('emulateFieldFiltering() should emulate operator on field', function () use ($before) {
        $before($this);
        /**
         * @var DatasourceCustomizer $datasourceCustomizer
         * @var CollectionCustomizer $customizer
         */
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->emulateFieldFiltering('reference');
        $datasourceCustomizer->getDatasource();
        /** @var ColumnSchema $field */
        $field = $datasourceCustomizer->getStack()->dataSource->getCollection('Book')->getFields()->get('reference');

        expect($field->getFilterOperators())
            ->toHaveCount(19)
            ->and($field->getFilterOperators())->toEqual(Rules::getAllowedOperatorsForColumnType($field->getColumnType()));
    });

    test('addHook() should add hook to the collection', function () use ($before) {
        $before($this);
        /**
         * @var DatasourceCustomizer $datasourceCustomizer
         * @var CollectionCustomizer $customizer
         */
        [$customizer, $datasourceCustomizer] = $this->bucket;
        $customizer->addHook('before', 'List', fn () => 'before hook');
        $datasourceCustomizer->getDatasource();
        /** @var HookCollection $computedCollection */
        $hookCollection = $datasourceCustomizer->getStack()->hook->getCollection('Book');
        $hooks = $this->invokeProperty($hookCollection, 'hooks')['List'];
        $beforeHooks = $this->invokeProperty($hooks, 'before');

        expect($beforeHooks)
            ->toHaveCount(1)
            ->and(call_user_func($beforeHooks[0]))
            ->toEqual('before hook');
    });
});
