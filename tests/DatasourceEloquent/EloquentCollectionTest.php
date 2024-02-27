<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentCollection;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\ThroughCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function Ozzie\Nest\describe;
use function Ozzie\Nest\test;

beforeEach(closure: function () {
    global $eloquentDatasource;
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
    $eloquentDatasource = new EloquentDatasource(TestCase::DB_CONFIG);
});


describe('addRelationships()', function () {
    test('should add OneToManySchema field when relation is a HasMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Author');

        expect($collection->getFields())->toHaveKey('books')
            ->and($collection->getFields()['books'])->toBeInstanceOf(OneToManySchema::class);
    });

    test('should add ManyToOneSchema field when relation is a BelongsTo with an inverse HasMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Book');

        expect($collection->getFields())->toHaveKey('author')
            ->and($collection->getFields()['author'])->toBeInstanceOf(ManyToOneSchema::class);
    });

    test('should add OneToOneSchema field when relation is a BelongsTo with an inverse HasOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Owner');

        expect($collection->getFields())->toHaveKey('user')
            ->and($collection->getFields()['user'])->toBeInstanceOf(OneToOneSchema::class);
    });

    test('should add OneToOneSchema field when relation is a HasOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('User');

        expect($collection->getFields())->toHaveKey('owner')
            ->and($collection->getFields()['owner'])->toBeInstanceOf(OneToOneSchema::class);
    });

    test('should add ManyToManySchema field when relation is a BelongsToMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('Book');

        expect($collection->getFields())->toHaveKey('reviews')
            ->and($collection->getFields()['reviews'])->toBeInstanceOf(ManyToManySchema::class);
    });

    test('should add a throughCollection to the datasource when the throughCollection doesn\'t exist', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        $collections = $eloquentDatasource->getCollections();

        expect($collections)->toHaveKey('CarOwner')
            ->and($collections->get('CarOwner'))->toBeInstanceOf(ThroughCollection::class);
    });
});

test('list() should return an array of records', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $_GET['fields'] = ['Book' => 'id, title, price'];
    $collection = $eloquentDatasource->getCollection('Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = ContextFilterFactory::buildPaginated($collection, $request, null);
    $projection = QueryStringParser::parseProjection($collection, $request);

    $records = $collection->list($caller, $filter, $projection);

    expect($records)->toBeArray()
        ->and($records[0])->toBeArray()
        ->and($records[0])->toHaveKeys(['id', 'title', 'price']);
});

test('create() should add a record in database and return it', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);

    $record = $collection->create(
        $caller,
        [
            'author_id'    => 1,
            'title'        => 'foo',
            'price'        => 100,
            'published_at' => '2023-07-06',
            'created_at'   => '2023-07-06',
            'updated_at'   => '2023-07-06',
        ]
    );

    expect($record)->toBeArray()
        ->and($record)->toHaveKeys(['id', 'author_id', 'title', 'price', 'published_at', 'created_at', 'updated_at'])
        ->and($record)->toMatchArray(
            [
                'author_id'    => 1,
                'title'        => 'foo',
                'price'        => 100,
                'published_at' => '2023-07-06',
                'created_at'   => '2023-07-06',
                'updated_at'   => '2023-07-06',
            ]
        );
});

test('update() should update a record in database', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter(
        new ConditionTreeLeaf(field: 'id', operator: Operators::EQUAL, value: '1')
    );

    $initialRecord = $collection->list($caller, $filter, new Projection())[0];
    $collection->update(
        $caller,
        $filter,
        [
            'title' => 'updated title',
        ]
    );
    $updatedRecord = $collection->list($caller, $filter, new Projection())[0];


    expect($updatedRecord['title'])->not->toEqual($initialRecord['title'])
        ->and($updatedRecord['title'])->toEqual('updated title');
});

test('delete() should remove the record in database', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter(
        new ConditionTreeLeaf(field: 'id', operator: Operators::EQUAL, value: '1')
    );

    $initialRecords = $collection->list($caller, $filter, new Projection());
    $collection->delete($caller, $filter);
    $records = $collection->list($caller, $filter, new Projection());

    expect($initialRecords)->not->toBeEmpty()
        ->and($records)->toBeEmpty();
});

test('aggregate() should remove the record in database', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter();
    $aggregation = new Aggregation(operation: 'Count', field: 'id');

    $aggregateResult = $collection->aggregate($caller, $filter, $aggregation);

    expect($aggregateResult)->toBeArray()
        ->and($aggregateResult[0])->toBeArray()
        ->and($aggregateResult[0])->toHaveKeys(['value', 'group']);
});
