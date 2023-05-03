<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\ComputedDefinition;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection\PublicationCollectionDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Write\WriteReplace\WriteReplaceCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;

function factoryCollectionCustomizer($collectionName = 'Book')
{
    $datasource = new Datasource();
    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true, filterOperators: [Operators::EQUAL, Operators::IN]),
            'title'     => new ColumnSchema(columnType: PrimitiveType::STRING),
            'reference' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'childId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN]),
            'authorId'  => new ColumnSchema(columnType: PrimitiveType::STRING, isReadOnly: true, isSortable: true),
            'author'    => new ManyToOneSchema(
                foreignKey: 'authorId',
                foreignKeyTarget: 'id',
                foreignCollection: 'Person',
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
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
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

    $collectionCategory = new Collection($datasource, 'Category');
    $collectionCategory->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
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
    buildAgent($datasource);

    $datasourceCustomizer = new DatasourceCustomizer();
    $datasourceCustomizer->addDatasource($datasource);

    $customizer = new CollectionCustomizer($datasourceCustomizer, $datasourceCustomizer->getStack(), $collectionName);

    return [$customizer, $datasourceCustomizer];
}

test('addField() should add a field to early collection', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();
    $data = [['id' => 1, 'title' => 'Foundation'], ['id' => 2, 'title' => 'Harry Potter']];

    $stack = $datasourceCustomizer->getStack();
    $earlyComputed = $stack->earlyComputed;
    $earlyComputed = mock($earlyComputed)
        ->shouldReceive('getCollection')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->earlyComputed->getCollection('Book'))
        ->getMock();

    invokeProperty($stack, 'earlyComputed', $earlyComputed);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $fieldDefinition = new ComputedDefinition(
        PrimitiveType::STRING,
        ['title'],
        fn ($records) => collect($records)->map(fn ($record) => $record['title'] . '-2022'),
        true
    );

    $customizer->addField('test', $fieldDefinition);
    \Mockery::close();

    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->earlyComputed->getCollection('Book');

    expect($computedCollection->getFields())
        ->toHaveKey('test')
        ->and($computedCollection->getComputed('test')->getValues($data))
        ->toEqual(collect(['Foundation-2022', 'Harry Potter-2022']));
});

test('addField() should add a field to late collection', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();
    $data = [['id' => 1, 'title' => 'Foundation'], ['id' => 2, 'title' => 'Harry Potter']];

    $stack = $datasourceCustomizer->getStack();
    $lateComputed = $stack->lateComputed;
    $lateComputed = mock($lateComputed)
        ->shouldReceive('getCollection')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->lateComputed->getCollection('Book'))
        ->getMock();

    invokeProperty($stack, 'lateComputed', $lateComputed);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $fieldDefinition = new ComputedDefinition(
        PrimitiveType::STRING,
        ['id'],
        fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
        false
    );

    $customizer->addManyToOneRelation('mySelf', 'Book', 'id', 'childId');
    $customizer->addField('mySelf', $fieldDefinition);

    \Mockery::close();

    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

    expect($computedCollection->getFields())
        ->toHaveKey('mySelf')
        ->and($computedCollection->getComputed('mySelf')->getValues($data))
        ->toEqual(collect(['1-Foo', '2-Foo']));
});

test('relations addManyToOneRelation() should add a many to one', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->relation->getCollection('Book');
    $bookRelation = mock($book)
        ->shouldReceive('addRelation')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Book'))
        ->getMock();
    $collections = $stack->relation->getCollections();
    $collections->put('Book', $bookRelation);

    invokeProperty($stack->relation, 'collections', $collections);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $fieldDefinition = new ComputedDefinition(
        PrimitiveType::STRING,
        ['id'],
        fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
        false
    );

    $customizer->addManyToOneRelation('mySelf', 'Book', 'id', 'childId');
    $customizer->addField('mySelf', $fieldDefinition);

    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

    expect($computedCollection->getFields())->toHaveKey('mySelf');
});

test('relations addOneToOneRelation() should add a one to one', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->relation->getCollection('Book');
    $bookRelation = mock($book)
        ->shouldReceive('addRelation')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Book'))
        ->getMock();
    $collections = $stack->relation->getCollections();
    $collections->put('Book', $bookRelation);

    invokeProperty($stack->relation, 'collections', $collections);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $fieldDefinition = new ComputedDefinition(
        PrimitiveType::STRING,
        ['id'],
        fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
        false
    );

    $customizer->addOneToOneRelation('newRelation', 'Person', 'Person.id', 'childId');
    $customizer->addField('newRelation', $fieldDefinition);

    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Book');

    expect($computedCollection->getFields())->toHaveKey('newRelation');
});

test('relations addOneToManyRelation() should add a one to many', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer('Category');

    $stack = $datasourceCustomizer->getStack();
    $category = $stack->relation->getCollection('Category');
    $categoryRelation = mock($category)
        ->shouldReceive('addRelation')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Category'))
        ->getMock();
    $collections = $stack->relation->getCollections();
    $collections->put('Category', $categoryRelation);

    invokeProperty($stack->relation, 'collections', $collections);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $fieldDefinition = new ComputedDefinition(
        PrimitiveType::STRING,
        ['id'],
        fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
        false
    );

    $customizer->addOneToManyRelation('newRelation', 'Person', 'id', 'Person.id');
    $customizer->addField('newRelation', $fieldDefinition);

    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Category');

    expect($computedCollection->getFields())->toHaveKey('newRelation');
});

test('relations addManyToManyRelation() should add a many to many', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer('Category');

    $stack = $datasourceCustomizer->getStack();
    $category = $stack->relation->getCollection('Category');
    $categoryRelation = mock($category)
        ->shouldReceive('addRelation')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->relation->getCollection('Category'))
        ->getMock();
    $collections = $stack->relation->getCollections();
    $collections->put('Category', $categoryRelation);

    invokeProperty($stack->relation, 'collections', $collections);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $fieldDefinition = new ComputedDefinition(
        PrimitiveType::STRING,
        ['id'],
        fn ($records) => collect($records)->map(fn ($record) => $record['id'] . '-Foo'),
        false
    );

    $customizer->addManyToManyRelation('newRelation', 'Person', '', 'PersonCategory', 'id', 'Person.id');
    $customizer->addField('newRelation', $fieldDefinition);

    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->lateComputed->getCollection('Category');

    expect($computedCollection->getFields())->toHaveKey('newRelation');
});

test('addSegment() should add a segment', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->segment->getCollection('Book');

    $customizer->addSegment('newSegment', fn () => [
        'field'    => 'id',
        'operator' => Operators::GREATER_THAN,
        'value'    => 1,
    ]);

    expect($book->getSegments()->toArray())->toEqual(['newSegment']);
});

test('emulateFieldSorting() should emulate sort on field', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->sort->getCollection('Book');

    $customizer->emulateFieldSorting('title');

    expect(invokeProperty($book, 'sorts'))->toHaveKey('title');
});

test('replaceFieldSorting() should replace sort on field', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->sort->getCollection('Book');

    $customizer->replaceFieldSorting('title', [['field' => 'title', 'ascending' => true]]);

    $sort = invokeProperty($book, 'sorts');
    expect($sort)
        ->toHaveKey('title')
        ->and($sort['title'])
        ->toEqual(new Sort([['field' => 'title', 'ascending' => true]]));
});

test('replaceSearch() should call the search decorator', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $search = $stack->search;
    $search = mock($search)
        ->shouldReceive('getCollection')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->search->getCollection('Book'))
        ->getMock();

    invokeProperty($stack, 'search', $search);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

    $condition = fn ($search) => [
        ['field' => 'title', 'operator' => Operators::EQUAL, 'value' => $search],
    ];
    $customizer->replaceSearch($condition);
    \Mockery::close();

    /** @var $searchCollection $searchCollection */
    $searchCollection = $datasourceCustomizer->getStack()->search->getCollection('Book');

    expect(invokeProperty($searchCollection, 'replacer'))->toEqual($condition);
});

test('disableCount() should disable count on the collection', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->schema->getCollection('Book');

    $customizer->disableCount();

    expect($book->isCountable())->toBeFalse();
});


test('addChart() should add a chart', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $book = $stack->chart->getCollection('Book');
    $customizer->addChart('newChart', fn ($context, $resultBuilder) => $resultBuilder->value(34));

    expect($book->getCharts()->toArray())->toEqual(['newChart']);
});

test('replaceFieldWriting() should update the field behavior of the collection', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();

    $stack = $datasourceCustomizer->getStack();
    $write = $stack->write;
    $write = mock($write)
        ->shouldReceive('getCollection')
        ->once()
        ->andReturn($datasourceCustomizer->getStack()->write->getCollection('Book'))
        ->getMock();

    invokeProperty($stack, 'write', $write);
    invokeProperty($datasourceCustomizer, 'stack', $stack);
    invokeProperty($customizer, 'stack', $stack);

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
    \Mockery::close();

    /** @var WriteReplaceCollection $writeReplaceCollection */
    $writeReplaceCollection = $datasourceCustomizer->getStack()->write->getCollection('Book');

    expect(invokeProperty($writeReplaceCollection, 'handlers'))->toEqual([$field => $condition]);
});

test('renameField() should rename a field in collection', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();
    $customizer->renameField('title', 'newTitle');
    /** @var ComputedCollection $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->renameField->getCollection('Book');

    expect($computedCollection->getFields())
        ->toHaveKey('newTitle')
        ->not->toHaveKey('title');
});

test('removeField() should remove a field in collection', function () {
    [$customizer, $datasourceCustomizer] = factoryCollectionCustomizer();
    $customizer->removeField('title');
    /** @var PublicationCollectionDecorator $computedCollection */
    $computedCollection = $datasourceCustomizer->getStack()->publication->getCollection('Book');

    expect($computedCollection->getFields())->not->toHaveKey('title');
});
