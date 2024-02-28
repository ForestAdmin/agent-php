<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\ContextFilterFactory;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\BaseDatasource\BaseCollection;
use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\Tests\TestCase;

use function Ozzie\Nest\test;

beforeEach(closure: function () {
    global $baseDatasource, $baseCollection, $request;
    $this->buildAgent(new Datasource(), ['projectDir' => __DIR__]);
    $this->initDatabase();
    $baseDatasource = new BaseDatasource(TestCase::DB_CONFIG);
    $baseCollection = new BaseCollection($baseDatasource, 'Book', 'books');
});


test('fetchFieldsFromTable() should fetch all fields from table', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $fields = $this->invokeMethod($baseCollection, 'fetchFieldsFromTable');

    expect($fields)->toHaveKey('columns')
        ->and($fields['columns'])->toHaveKeys(['id','author_id','title','price','published_at','created_at','updated_at'])
        ->and($fields)->toHaveKey('primaries')
        ->and($fields['primaries'])->toEqual(['id']);
});

test('makeColumns() should add a columnSchema for each field fetch in the table', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $fields = $this->invokeMethod($baseCollection, 'fetchFieldsFromTable');
    //makeColumns() is call in construct()

    expect($baseCollection->getFields())->toHaveCount(count($fields['columns']))
        ->and($baseCollection->getFields())->each(fn ($column) => get_class($column) === ColumnSchema::class);
});

test('list() should return an array of records', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = ContextFilterFactory::buildPaginated($baseCollection, $request, null);
    $projection = QueryStringParser::parseProjection($baseCollection, $request);

    $records = $baseCollection->list($caller, $filter, $projection);

    expect($records)->toBeArray()
        ->and($records[0])->toBeArray()
        ->and($records[0])->toHaveKeys(['id', 'author_id', 'title', 'price', 'published_at', 'created_at', 'updated_at']);
});

test('create() should add a record in database and return it', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);

    $record = $baseCollection->create(
        $caller,
        [
            'author_id'    => 1,
            'title'        => 'foo',
            'price'        => 100,
            'published_at' => '2023-07-06T12:10:12+02:00',
        ]
    );

    expect($record)->toBeArray()
        ->and($record)->toHaveKeys(['id', 'author_id', 'title', 'price', 'published_at', 'created_at', 'updated_at'])
        ->and($record)->toMatchArray(
            [
                'author_id'    => 1,
                'title'        => 'foo',
                'price'        => 100,
                'published_at' => '2023-07-06T12:10:12+02:00',
                'created_at'   => null,
                'updated_at'   => null,
            ]
        );
});

test('update() should update a record in database', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter(
        new ConditionTreeLeaf(field: 'id', operator: Operators::EQUAL, value: '1')
    );

    $initialRecord = $baseCollection->list($caller, $filter, new Projection())[0];
    $baseCollection->update(
        $caller,
        $filter,
        [
            'title' => 'updated title',
        ]
    );
    $updatedRecord = $baseCollection->list($caller, $filter, new Projection())[0];


    expect($updatedRecord['title'])->not->toEqual($initialRecord['title'])
        ->and($updatedRecord['title'])->toEqual('updated title');
});

test('delete() should remove the record in database', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter(
        new ConditionTreeLeaf(field: 'id', operator: Operators::EQUAL, value: '1')
    );

    $initialRecords = $baseCollection->list($caller, $filter, new Projection());
    $baseCollection->delete($caller, $filter);
    $records = $baseCollection->list($caller, $filter, new Projection());

    expect($initialRecords)->not->toBeEmpty()
        ->and($records)->toBeEmpty();
});


test('aggregate() should count the records in database', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);
    $filter = new Filter();
    $aggregation = new Aggregation(operation: 'Count', field: 'id');

    $aggregateResult = $baseCollection->aggregate($caller, $filter, $aggregation);

    expect($aggregateResult)->toBeArray()
        ->and($aggregateResult[0])->toBeArray()
        ->and($aggregateResult[0])->toHaveKeys(['value', 'group']);
});


test('renderChart() should throw an exception', function () {
    /** @var BaseCollection $baseCollection */
    global $baseCollection;
    $request = Request::createFromGlobals();
    $caller = QueryStringParser::parseCaller($request);

    expect(fn () => $baseCollection->renderChart($caller, 'fooChart', [1]))
        ->toThrow(ForestException::class, "🌳🌳🌳 Chart fooChart is not implemented.");
});
