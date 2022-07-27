<?php

namespace ForestAdmin\AgentPHP\Tests\Agent\Unit;

use Firebase\JWT\JWT;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Sort;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\Rules;

use function ForestAdmin\cache;
use function ForestAdmin\config;

dataset('QueryStringParserCollection', static function () {
    yield $datasource = new Datasource();
    $usersCollection = new Collection($datasource, 'users');
    $usersCollection->addFields(
        [
            'id'            => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'name'          => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: Rules::BASE_OPERATORS),
            'email'         => new ColumnSchema(columnType: PrimitiveType::STRING),
            'rememberToken' => new ColumnSchema(columnType: PrimitiveType::BOOLEAN),
            'house'         => new OneToOneSchema(
                originKey: 'id',
                originKeyTarget: 'id',
                foreignCollection: 'houses',
            ),
        ]
    );
    $usersCollection->addSegment('fake-segment');
    $housesCollection = new Collection($datasource, 'houses');
    $housesCollection->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'address' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'city'    => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $datasource->addCollection($usersCollection);
    $datasource->addCollection($housesCollection);
    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
        'envSecret'  => '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d',
    ];
    new AgentFactory($options);
    cache('datasource', $datasource);
});

test('parseConditionTree() should work when passed in the querystring (for list)', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['filters' => '{"field":"name","operator":"equal","value":"BMW"}']);

    expect(QueryStringParser::parseConditionTree($collection, $request))->toEqual(new ConditionTreeLeaf('name', 'Equal', 'BMW'));
})->with('QueryStringParserCollection');

test('parseConditionTree() should work when passed in the body', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $data = [
        'data' => [
            'attributes' => [
                'all_records_subset_query' => [
                    'filters' => '{"field":"name","operator":"equal","value":"BMW"}',
                ],
            ],
        ],
    ];
    $request = new Request(request: $data);

    expect(QueryStringParser::parseConditionTree($collection, $request))->toEqual(new ConditionTreeLeaf('name', 'Equal', 'BMW'));
})->with('QueryStringParserCollection');

test('parseConditionTree() should return null when not provided', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request();

    expect(QueryStringParser::parseConditionTree($collection, $request))->toBeNull();
})->with('QueryStringParserCollection');

test('parseProjection() should convert the request to a valid projection', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['fields[users]' => 'id']);

    expect(QueryStringParser::parseProjection($collection, $request))->toEqual(new Projection(['id']));
})->with('QueryStringParserCollection');

test('parseProjection() when the request does no contains fields, should return a projection with all the fields', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['fields[users]' => '']);
    expect(QueryStringParser::parseProjection($collection, $request))->toEqual(new Projection(['id', 'name', 'email', 'rememberToken', 'house:id', 'house:address', 'house:city']));
})->with('QueryStringParserCollection');

test('parseProjection() when the request does not contains fields at all, should return all the projection fields', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request();

    expect(QueryStringParser::parseProjection($collection, $request))->toEqual(new Projection(['id', 'name', 'email', 'rememberToken', 'house:id', 'house:address', 'house:city']));
})->with('QueryStringParserCollection');

test('parseProjectionWithPks() on a collection with relationships, should convert the request to a valid projection', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['fields[users]' => 'id,house', 'fields[house]' => 'address']);

    expect(QueryStringParser::parseProjectionWithPks($collection, $request))->toEqual(new Projection(['id', 'house:address', 'house:id']));
})->with('QueryStringParserCollection');

test('parseProjectionWithPks() when the request does not contain the primary keys, should return the requested project with the primary keys', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('houses');
    $request = new Request(['fields[house]' => 'address']);

    expect(QueryStringParser::parseProjectionWithPks($collection, $request))->toEqual(new Projection(['id', 'address', 'city']));
})->with('QueryStringParserCollection');

test('parseSearch() should return null when not provided', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request();

    expect(QueryStringParser::parseSearch($collection, $request))->toBeNull();
})->with('QueryStringParserCollection');

test('parseSearch() should throw an error when the collection is not searchable', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('houses');
    $collection->setSearchable(false);
    $request = new Request(['search' => 'searched argument']);

    expect(fn () => QueryStringParser::parseSearch($collection, $request))->toThrow(ForestException::class, '🌳🌳🌳 Collection is not searchable');
})->with('QueryStringParserCollection');

test('parseSearch() should return the query search parameter', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $collection->setSearchable(true);
    $request = new Request(['search' => 'searched argument']);

    expect(QueryStringParser::parseSearch($collection, $request))->toEqual('searched argument');
})->with('QueryStringParserCollection');

test('parseSearch() should convert the query search parameter as string', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $collection->setSearchable(true);
    $request = new Request(['search' => 1234]);

    expect(QueryStringParser::parseSearch($collection, $request))->toEqual('1234');
})->with('QueryStringParserCollection');

test('parseSearch() should work when passed in the body (actions)', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $collection->setSearchable(true);
    $data = [
        'data' => [
            'attributes' => [
                'all_records_subset_query' => [
                    'search' => 'searched argument',
                ],
            ],
        ],
    ];
    $request = new Request(request: $data);

    expect(QueryStringParser::parseSearch($collection, $request))->toEqual('searched argument');
})->with('QueryStringParserCollection');

test('parseSearchExtended() should return the query searchExtended parameter', function () {
    $request = new Request(['searchExtended' => true]);

    expect(QueryStringParser::parseSearchExtended($request))->toBeTrue();
});

test('parseSearchExtended() should return true for "1" string', function () {
    $request = new Request(['searchExtended' => '1']);

    expect(QueryStringParser::parseSearchExtended($request))->toBeTrue();
});

test('parseSearchExtended() should return false for false parameter', function () {
    $request = new Request(['searchExtended' => false]);

    expect(QueryStringParser::parseSearchExtended($request))->toBeFalse();
});

test('parseSearchExtended() should return false for falsy "0" string', function () {
    $request = new Request(['searchExtended' => '0']);

    expect(QueryStringParser::parseSearchExtended($request))->toBeFalse();
});

test('parseSegment() should return null when no segment is provided', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request();

    expect(QueryStringParser::parseSegment($collection, $request))->toBeNull();
})->with('QueryStringParserCollection');

test('parseSegment() should return the segment name when it exists', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['segment' => 'fake-segment']);

    expect(QueryStringParser::parseSegment($collection, $request))->toEqual('fake-segment');
})->with('QueryStringParserCollection');

test('parseSegment() should work when passed in the body (actions)', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $data = [
        'data' => [
            'attributes' => [
                'all_records_subset_query' => [
                    'segment' => 'fake-segment',
                ],
            ],
        ],
    ];
    $request = new Request(request: $data);

    expect(QueryStringParser::parseSegment($collection, $request))->toEqual('fake-segment');
})->with('QueryStringParserCollection');

test('parseSegment() should throw a ForestException when the segment name does not exist', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['segment' => 'fake-segment-that-dont-exist']);

    expect(fn () => QueryStringParser::parseSegment($collection, $request))->toThrow(ForestException::class, '🌳🌳🌳 Invalid segment: fake-segment-that-dont-exist');
})->with('QueryStringParserCollection');

test('parseCaller() should return the timezone and the users', function ($datasource) {
    $user = [
        'id'          => 1,
        'email'       => 'john.doe@domain.com',
        'firstName'   => 'John',
        'lastName'    => 'Doe',
        'team'        => 'Developers',
        'renderingId' => '10',
        'tags'        => [],
    ];

    $token = JWT::encode($user, config('envSecret'), 'HS256');
    $request = new Request(query: ['timezone' => 'America/Los_Angeles'], server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

    expect(QueryStringParser::parseCaller($request))->toEqual(new Caller(
        1,
        'john.doe@domain.com',
        'John',
        'Doe',
        'Developers',
        '10',
        [],
        'America/Los_Angeles'
    ));
})->with('QueryStringParserCollection');

test('parseCaller() should throw a ForestException when the timezone is missing', function () {
    $request = new Request();

    expect(fn () => QueryStringParser::parseCaller($request))->toThrow(ForestException::class, '🌳🌳🌳 Missing timezone');
});

test('parseCaller() should throw a ForestException when the timezone is invalid', function () {
    $request = new Request(['timezone' => 'ThisTZ/Donotexist']);

    expect(fn () => QueryStringParser::parseCaller($request))->toThrow(ForestException::class, '🌳🌳🌳 Invalid timezone: ThisTZ/Donotexist');
});

test('parsePagination() should return the pagination parameters', function () {
    $request = new Request(['page' => ['size' => '10', 'number' => '3']]);

    expect(QueryStringParser::parsePagination($request))->toEqual(new Page(20, 10));
});

test('parsePagination() when context does not provide the pagination parameters should return the default limit 15 skip 0', function () {
    $request = new Request();

    expect(QueryStringParser::parsePagination($request))->toEqual(new Page(0, 15));
});

test('parsePagination() when context provides invalid values should throw a ForestExceptio', function () {
    $request = new Request(['page' => ['size' => '-5', 'number' => 'NaN']]);

    expect(fn () => QueryStringParser::parsePagination($request))->toThrow(ForestException::class, '🌳🌳🌳 Invalid pagination [limit: -5, skip: NaN]');
});

test('parseSort() should sort by pk ascending when not sort is given', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request();

    expect(QueryStringParser::parseSort($collection, $request))->toEqual(new Sort(['id']));
})->with('QueryStringParserCollection');

test('parseSort() should sort by the request field and order when given', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['sort' => '-name']);

    expect(QueryStringParser::parseSort($collection, $request))->toEqual(new Sort(['-name']));
})->with('QueryStringParserCollection');

test('parseSort() should throw a ForestException when the requested sort is invalid', function ($datasource) {
    /** @var Collection $collection */
    $collection = $datasource->getCollection('users');
    $request = new Request(['sort' => '-fieldThatDoNotExist']);

    expect(fn () => QueryStringParser::parseSort($collection, $request))->toThrow(ForestException::class, '🌳🌳🌳 Invalid sort: -fieldThatDoNotExist');
})->with('QueryStringParserCollection');
