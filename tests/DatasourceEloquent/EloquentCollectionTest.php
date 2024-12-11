<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentCollection;
use ForestAdmin\AgentPHP\DatasourceEloquent\EloquentDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\ThroughCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\ConditionTreeFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicOneToOneSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

beforeEach(closure: function () {
    global $eloquentDatasource;
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
    $eloquentDatasource = new EloquentDatasource(TestCase::DB_CONFIG, 'eloquent_collection', true);
});

describe('addRelationships()', function () {
    test('should add OneToManySchema field when relation is a HasMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Author');

        expect($collection->getFields())->toHaveKey('books')
            ->and($collection->getFields()['books'])->toBeInstanceOf(OneToManySchema::class);
    });

    test('should add ManyToOneSchema field when relation is a BelongsTo with an inverse HasMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');

        expect($collection->getFields())->toHaveKey('author')
            ->and($collection->getFields()['author'])->toBeInstanceOf(ManyToOneSchema::class);
    });

    test('should add OneToOneSchema field when relation is a BelongsTo with an inverse HasOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Owner');

        expect($collection->getFields())->toHaveKey('user')
            ->and($collection->getFields()['user'])->toBeInstanceOf(ManyToOneSchema::class);
    });

    test('should add OneToOneSchema field when relation is a HasOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_User');

        expect($collection->getFields())->toHaveKey('owner')
            ->and($collection->getFields()['owner'])->toBeInstanceOf(OneToOneSchema::class);
    });

    test('should add ManyToManySchema field when relation is a BelongsToMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');

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

    test('should add a PolymorphicOneToMany field when relation is a MorphMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');

        expect($collection->getFields())->toHaveKey('comments')
            ->and($collection->getFields()['comments'])->toBeInstanceOf(PolymorphicOneToManySchema::class);
    });

    test('should add a PolymorphicManyToOne field when relation is a MorphTo', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Comment');

        expect($collection->getFields())->toHaveKey('commentable')
            ->and($collection->getFields()['commentable'])->toBeInstanceOf(PolymorphicManyToOneSchema::class);
    });

    test('should add a PolymorphicOneToOne field when relation is a MorphOne', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_User');

        expect($collection->getFields())->toHaveKey('comment')
            ->and($collection->getFields()['comment'])->toBeInstanceOf(PolymorphicOneToOneSchema::class);
    });
});

test('list() should return an array of records', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $_GET['fields'] = ['ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book' => 'id, title, price'];
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new PaginatedFilter(
        conditionTree: ConditionTreeFactory::intersect([QueryStringParser::parseConditionTree($collection, $request)]),
        search: QueryStringParser::parseSearch($collection, $request),
        searchExtended: QueryStringParser::parseSearchExtended($request),
        segment: QueryStringParser::parseSegment($collection, $request),
        sort: QueryStringParser::parseSort($collection, $request),
        page: QueryStringParser::parsePagination($request)
    );

    $projection = QueryStringParser::parseProjection($collection, $request);
    $records = $collection->list($caller, $filter, $projection);

    expect($records)->toBeArray()
        ->and($records[0])->toBeArray()
        ->and($records[0])->toHaveKeys(['id', 'title', 'price']);
});

test('create() should add a record in database and return it', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);

    $record = $collection->create(
        $caller,
        [
            'author_id'    => 1,
            'title'        => 'foo',
            'price'        => 100,
            'published_at' => '2023-07-06',
            'created_at'   => null,
            'updated_at'   => null,
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
                'created_at'   => null,
                'updated_at'   => null,
            ]
        );
});

test('update() should update a record in database', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
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
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
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

test('aggregate() should count the records in database', function () {
    /** @var EloquentDatasource $baseCollection */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter();
    $aggregation = new Aggregation(operation: 'Count', field: 'id');

    $aggregateResult = $collection->aggregate($caller, $filter, $aggregation);

    expect($aggregateResult)->toBeArray()
        ->and($aggregateResult[0])->toBeArray()
        ->and($aggregateResult[0])->toHaveKeys(['value', 'group']);
});

describe('addRelationships() without support polymorphic Relations', function () {
    beforeEach(closure: function () {
        global $eloquentDatasource;
        $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
        $this->initDatabase();
        $eloquentDatasource = new EloquentDatasource(TestCase::DB_CONFIG, false);
    });

    test('should not add a PolymorphicOneToMany field when relation is a MorphMany', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');

        expect($collection->getFields())->not()->toHaveKey('comments');
    });

    test('should not add a PolymorphicManyToOne field when relation is a MorphTo', function () {
        /** @var EloquentDatasource $eloquentDatasource */
        global $eloquentDatasource;
        /** @var EloquentCollection $collection */
        $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Comment');

        expect($collection->getFields())->not()->toHaveKey('commentable');
    });
});

test('serialize should return an array of records', function () {
    /** @var EloquentDatasource $eloquentDatasource */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');

    $serialize = $collection->__serialize();
    expect($serialize)->toBeArray()
        ->and($serialize['fields'])->toEqual($collection->getFields())
        ->and($serialize['actions'])->toEqual($collection->getActions())
        ->and($serialize['segments'])->toEqual($collection->getSegments())
        ->and($serialize['charts'])->toEqual($collection->getCharts())
        ->and($serialize['schema'])->toEqual($collection->getSchema())
        ->and($serialize['dataSource'])->toEqual($collection->getDataSource())
        ->and($serialize['name'])->toEqual($collection->getName())
        ->and($serialize['tableName'])->toEqual($collection->getTableName())
        ->and($serialize['model'])->toEqual('ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Book');
});

test('unserialize should return an EloquentCollection', function () {
    /** @var EloquentDatasource $eloquentDatasource */
    global $eloquentDatasource;
    $collection = $eloquentDatasource->getCollection('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book');

    $serialize = $collection->__serialize();
    $collection->__unserialize($serialize);

    expect($collection)->toBeInstanceOf(EloquentCollection::class)
        ->and($collection->getName())->toEqual('ForestAdmin_AgentPHP_Tests_DatasourceEloquent_Models_Book')
        ->and($collection->getFields())->toEqual($serialize['fields'])
        ->and($collection->getActions())->toEqual($serialize['actions'])
        ->and($collection->getSegments())->toEqual($serialize['segments'])
        ->and($collection->getCharts())->toEqual($serialize['charts'])
        ->and($collection->getSchema())->toEqual($serialize['schema'])
        ->and($collection->getDataSource())->toEqual($serialize['dataSource'])
        ->and($collection->getTableName())->toEqual($serialize['tableName'])
        ->and($collection->getModel())->toBeInstanceOf('ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Book');
});
