<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToManySchema;
use Symfony\Component\HttpKernel\Exception\HttpException;

function factoryQueryStringParser()
{
    $datasource = new Datasource();
    $collectionCategory = new Collection($datasource, 'Category');
    $collectionCategory->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::EQUAL], isPrimaryKey: true),
            'label' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $collectionCategory->setSegments(['fake-segment']);

    $collectionUser = new Collection($datasource, 'User');
    $collectionUser->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'first_name' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'last_name'  => new ColumnSchema(columnType: PrimitiveType::STRING),
            'cars'       => new OneToManySchema(
                originKey: 'user_id',
                originKeyTarget: 'id',
                foreignCollection: 'Car',
            ),
        ]
    );
    $collectionUser->setSearchable(true);

    $collectionCar = new Collection($datasource, 'Car');
    $collectionCar->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN, Operators::EQUAL], isPrimaryKey: true),
            'model'   => new ColumnSchema(columnType: PrimitiveType::STRING),
            'brand'   => new ColumnSchema(columnType: PrimitiveType::STRING),
            'user_id' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'user'    => new ManyToOneSchema(
                foreignKey: 'user_id',
                foreignKeyTarget: 'id',
                foreignCollection: 'User',
            ),
        ]
    );

    $datasource->addCollection($collectionCategory);
    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCar);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
    ];
    (new AgentFactory($options, []))->addDatasource($datasource)->build();

    return compact('collectionCategory', 'collectionUser', 'collectionCar');
}

test('parseConditionTree() should return null when not provided', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    expect(QueryStringParser::parseConditionTree($collectionCategory, Request::createFromGlobals()))
        ->toBeNull();
});

test('parseConditionTree() should work when passed in the querystring (for list)', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['filters'] = json_encode([
        'aggregator' => 'And',
        'conditions' => [
            ['field' => 'id', 'operator' => 'Equal', 'value' => '123e4567-e89b-12d3-a456-426614174000'],
        ],
    ], JSON_THROW_ON_ERROR);

    $parse = QueryStringParser::parseConditionTree($collectionCategory, Request::createFromGlobals());

    expect($parse)
        ->toBeInstanceOf(ConditionTreeLeaf::class)
        ->and($parse->toArray())
        ->toEqual(
            [
                'field'    => 'id',
                'operator' => 'Equal',
                'value'    => '123e4567-e89b-12d3-a456-426614174000',
            ]
        );
});

test('parseConditionTree() should work when passed in the body (for charts)', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['filters'] = json_encode([
        'field'    => 'id',
        'operator' => 'Equal',
        'value'    => '123e4567-e89b-12d3-a456-426614174000',
    ], JSON_THROW_ON_ERROR);

    $parse = QueryStringParser::parseConditionTree($collectionCategory, Request::createFromGlobals());

    expect($parse)
        ->toBeInstanceOf(ConditionTreeLeaf::class)
        ->and($parse->toArray())
        ->toEqual(
            [
                'field'    => 'id',
                'operator' => 'Equal',
                'value'    => '123e4567-e89b-12d3-a456-426614174000',
            ]
        );
});

test('parseConditionTree() should work when passed in the body (for actions)', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['data']['attributes']['all_records_subset_query']['filters'] = json_encode([
        'field'    => 'id',
        'operator' => 'Equal',
        'value'    => '123e4567-e89b-12d3-a456-426614174000',
    ], JSON_THROW_ON_ERROR);

    $parse = QueryStringParser::parseConditionTree($collectionCategory, Request::createFromGlobals());

    expect($parse)
        ->toBeInstanceOf(ConditionTreeLeaf::class)
        ->and($parse->toArray())
        ->toEqual(
            [
                'field'    => 'id',
                'operator' => 'Equal',
                'value'    => '123e4567-e89b-12d3-a456-426614174000',
            ]
        );
});

test('parseConditionTree() throw when the collection does not supports the requested operators', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['filters'] = json_encode([
        'aggregator' => 'And',
        'conditions' => [
            ['field' => 'id', 'operator' => 'Greater_Than', 'value' => '123e4567-e89b-12d3-a456-426614174000'],
        ],
    ], JSON_THROW_ON_ERROR);

    expect(fn () => QueryStringParser::parseConditionTree($collectionCategory, Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 The allowed operators are: Equal');
});

test('parseProjection() on a flat collection on a well formed request should convert the request to a valid projection', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['fields'] = ['Category' => 'id'];

    $projection = QueryStringParser::parseProjection($collectionCategory, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection('id')));
});

test('parseProjection() on a flat collection on a well formed request when the request does no contain fields should return a projection with all the fields', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['fields'] = ['User' => ''];

    $projection = QueryStringParser::parseProjection($collectionCategory, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection(['id', 'label'])));
});

test('parseProjection() on a flat collection on a well formed request when the request does not contain the primary keys should return the requested project without the primary keys', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['fields'] = ['Category' => 'label'];

    $projection = QueryStringParser::parseProjection($collectionCategory, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection('label')));
});

test('parseProjection() on a flat collection on a malformed request when the request does not contains fields at all should return all the projection fields', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $projection = QueryStringParser::parseProjection($collectionCategory, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection(['id', 'label'])));
});

test('parseProjection() on a collection with relationships should convert the request to a valid projection', function () {
    $collectionCar = factoryQueryStringParser()['collectionCar'];

    $_GET['fields'] = [
        'Car'  => 'id, model, user',
        'user' => 'last_name',
    ];

    $projection = QueryStringParser::parseProjection($collectionCar, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection(['id', 'model', 'user:last_name'])));
});

test('parseProjection() on a flat collection on a request with an unknown field should return a ForestException error', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['fields'] = ['Category' => 'foo'];

    expect(fn () => QueryStringParser::parseProjection($collectionCategory, Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Invalid projection');
});

test('parseProjectionWithPks() when the request does not contain the primary keys should return the requested project with the primary keys', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $_GET['fields'] = ['Category' => 'label'];

    $projection = QueryStringParser::parseProjectionWithPks($collectionCategory, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection(['label', 'id'])));
});

test('parseProjectionWithPks() when the request does not contain the primary keys on a collection with relationships should convert the request to a valid projection', function () {
    $collectionCar = factoryQueryStringParser()['collectionCar'];

    $_GET['fields'] = [
        'Car'  => 'id, user',
        'user' => 'last_name',
    ];

    $projection = QueryStringParser::parseProjectionWithPks($collectionCar, Request::createFromGlobals());

    expect($projection)->toEqual((new Projection(['id', 'user:last_name', 'user:id'])));
});

test('parseSearch() should return null when not provided', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $parseSearch = QueryStringParser::parseSearch($collectionCategory, Request::createFromGlobals());

    expect($parseSearch)->toBeNull();
});

test('parseSearch() should throw an error when the collection is not searchable', function () {
    $collectionUser = factoryQueryStringParser()['collectionUser'];

    $_GET['search'] = 'searched argument';
    $parseSearch = QueryStringParser::parseSearch($collectionUser, Request::createFromGlobals());

    expect($parseSearch)->toEqual('searched argument');
});

test('parseSearch() should convert the query search parameter as string', function () {
    $collectionUser = factoryQueryStringParser()['collectionUser'];

    $_GET['search'] = 1234;
    $parseSearch = QueryStringParser::parseSearch($collectionUser, Request::createFromGlobals());

    expect($parseSearch)->toEqual('1234');
});

test('parseSearch() should work when passed in the body (actions)', function () {
    $collectionUser = factoryQueryStringParser()['collectionUser'];

    $_GET['data']['attributes']['all_records_subset_query']['search'] = 'searched argument';
    $parseSearch = QueryStringParser::parseSearch($collectionUser, Request::createFromGlobals());

    expect($parseSearch)->toEqual('searched argument');
});

test('parseSearch() on a Collection not searchable should return a ForestException error', function () {
    $collectionCar = factoryQueryStringParser()['collectionCar'];

    $_GET['data']['attributes']['all_records_subset_query']['search'] = 'searched argument';

    expect(fn () => QueryStringParser::parseSearch($collectionCar, Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Collection is not searchable');
});

test('parseSearchExtended() should return the query searchExtended parameter', function () {
    $_GET['searchExtended'] = true;
    $parseSearch = QueryStringParser::parseSearchExtended(Request::createFromGlobals());

    expect($parseSearch)->toBeTrue();
});

test('parseSearchExtended() should return false for falsy "0" string', function () {
    $_GET['searchExtended'] = '0';
    $parseSearch = QueryStringParser::parseSearchExtended(Request::createFromGlobals());

    expect($parseSearch)->toBeFalse();
});

test('parseSearchExtended() should return false for falsy "false" string', function () {
    $_GET['searchExtended'] = '0';
    $parseSearch = QueryStringParser::parseSearchExtended(Request::createFromGlobals());

    expect($parseSearch)->toBeFalse();
});

test('parseSegment() should return null when no segment is provided', function () {
    $collectionUser = factoryQueryStringParser()['collectionUser'];
    $parseSegment = QueryStringParser::parseSegment($collectionUser, Request::createFromGlobals());

    expect($parseSegment)->toBeNull();
});

test('parseSegment() should return the segment name when it exists', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];
    $_GET['segment'] = 'fake-segment';
    $parseSegment = QueryStringParser::parseSegment($collectionCategory, Request::createFromGlobals());

    expect($parseSegment)->toEqual('fake-segment');
});

test('parseSegment() should throw when the segment name does not exist', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];
    $_GET['segment'] = 'fake-segment-that-dont-exist';

    expect(fn () => QueryStringParser::parseSegment($collectionCategory, Request::createFromGlobals()))
        ->toThrow(ForestException::class, 'Invalid segment: fake-segment-that-dont-exist');
});

test('parseSegment() should work when passed in the body (actions)', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];
    $_GET['data']['attributes']['all_records_subset_query']['segment'] = 'fake-segment';

    $parseSegment = QueryStringParser::parseSegment($collectionCategory, Request::createFromGlobals());

    expect($parseSegment)->toEqual('fake-segment');
});

test('parseCaller() should return the timezone and the user', function () {
    factoryQueryStringParser();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'America/Los_Angeles';

    $projection = QueryStringParser::parseCaller(Request::createFromGlobals());

    expect($projection)
        ->toBeInstanceOf(Caller::class)
        ->and($projection->getId())
        ->toEqual(1)
        ->and($projection->getValue('timezone'))
        ->toEqual('America/Los_Angeles');
});

test('parseCaller() should throw a ValidationError when the timezone is missing', function () {
    factoryQueryStringParser();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;

    expect(fn () => QueryStringParser::parseCaller(Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Missing timezone');
});

test('parseCaller() should throw a ValidationError when the timezone is invalid', function () {
    factoryQueryStringParser();
    $_SERVER['HTTP_AUTHORIZATION'] = BEARER;
    $_GET['timezone'] = 'ThisTZ/Donotexist';

    expect(fn () => QueryStringParser::parseCaller(Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Invalid timezone');
});

test('parseCaller() should throw a HttpException when the user is not connected', function () {
    factoryQueryStringParser();
    $_SERVER['HTTP_AUTHORIZATION'] = '';

    expect(fn () => QueryStringParser::parseCaller(Request::createFromGlobals()))
        ->toThrow(HttpException::class, 'You must be logged in to access at this resource');
});

test('parsePagination() should return the pagination parameters', function () {
    factoryQueryStringParser();
    $_GET['page'] = [
        'size'   => 10,
        'number' => 3,
    ];

    $pagination = QueryStringParser::parsePagination(Request::createFromGlobals());

    expect($pagination)->toEqual(new Page(20, 10));
});

test('parsePagination() when context does not provide the pagination parameters should return the default limit 15 skip 0', function () {
    factoryQueryStringParser();

    $pagination = QueryStringParser::parsePagination(Request::createFromGlobals());

    expect($pagination)->toEqual(new Page(0, 15));
});

test('parsePagination() when context provides invalid values should return a ForestException error', function () {
    factoryQueryStringParser();
    $_GET['page'] = [
        'size'   => -5,
        'number' => 'NaN',
    ];

    expect(fn () => QueryStringParser::parsePagination(Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Invalid pagination [limit: -5, skip: NaN]');
});

test('parseSort() should sort by pk ascending when not sort is given', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];

    $sort = QueryStringParser::parseSort($collectionCategory, Request::createFromGlobals());

    expect($sort)->toEqual(new Sort([['field' => 'id', 'ascending' => true]]));
});

test('parseSort() should sort by the request field and order when given', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];
    $_GET['sort'] = '-label';
    $sort = QueryStringParser::parseSort($collectionCategory, Request::createFromGlobals());

    expect($sort)->toEqual(new Sort([['field' => 'label', 'ascending' => false]]));
});

test('parseSort() should throw a ForestException when the requested sort is invalid', function () {
    $collectionCategory = factoryQueryStringParser()['collectionCategory'];
    $_GET['sort'] = '-fieldThatDoNotExist';

    expect(fn () => QueryStringParser::parseSort($collectionCategory, Request::createFromGlobals()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Category.fieldThatDoNotExist');
});
