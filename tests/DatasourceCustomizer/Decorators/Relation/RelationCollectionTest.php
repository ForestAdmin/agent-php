<?php

use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Relation\RelationCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Aggregation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;

function factoryRelationCollection()
{
    $passportRecords = [
        [
            'id'        => 101,
            'issueDate' => '2010-01-01',
            'ownerId'   => 202,
            'pictureId' => 301,
            'picture'   => ['pictureId' => 301, 'filename' => 'pic1.jpg'],
        ],
        [
            'id'        => 102,
            'issueDate' => '2017-01-01',
            'ownerId'   => 201,
            'pictureId' => 302,
            'picture'   => ['pictureId' => 302, 'filename' => 'pic2.jpg'],
        ],
        [
            'id'        => 103,
            'issueDate' => '2017-02-05',
            'ownerId'   => null,
            'pictureId' => 303,
            'picture'   => ['pictureId' => 303, 'filename' => 'pic3.jpg'],
        ],
    ];

    $personsRecords = [
        ['id' => 201, 'otherId' => 201, 'name' => 'Sharon J. Whalen'],
        ['id' => 202, 'otherId' => 202, 'name' => 'Mae S. Waldron'],
        ['id' => 203, 'otherId' => 203, 'name' => 'Joseph P. Rodriguez'],
    ];

    $datasource = new Datasource();
    $collectionPicture = new Collection($datasource, 'Picture');
    $collectionPicture->addFields(
        [
            'id'       => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'filename' => new ColumnSchema(columnType: PrimitiveType::STRING),
            'otherId'  => new ColumnSchema(columnType: PrimitiveType::NUMBER),
        ]
    );

    $collectionPassport = new Collection($datasource, 'Passport');
    $collectionPassport->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'issueDate' => new ColumnSchema(columnType: PrimitiveType::DATEONLY),
            'ownerId'   => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN]),
            'pictureId' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'picture'   => new ManyToOneSchema(foreignKey: 'pictureId', foreignKeyTarget: 'id', foreignCollection: 'Picture'),
        ]
    );
    $collectionPassport = mock($collectionPassport)
        ->shouldReceive('list')
        ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
        ->andReturnUsing(
            function (Caller $caller, PaginatedFilter $filter, Projection $projection) use ($collectionPassport, $passportRecords) {
                if ($filter->getConditionTree()) {
                    $passportRecords = $filter->getConditionTree()->apply($passportRecords, $collectionPassport, 'Europe/Paris');
                }
                if ($filter->getSort()) {
                    $passportRecords = $filter->getSort()->apply($passportRecords);
                }

                return $projection->apply($passportRecords)->toArray();
            }
        )
        ->shouldReceive('aggregate')
        ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class))
        ->andReturnUsing(
            function (Caller $caller, Filter $filter, Aggregation $aggregation) use ($passportRecords) {
                return $aggregation->apply($passportRecords, 'Europe/Paris');
            }
        )->getMock();

    $collectionPerson = new Collection($datasource, 'Person');
    $collectionPerson->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::EQUAL, Operators::IN], isPrimaryKey: true),
            'otherId' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::IN]),
            'name'    => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: [Operators::IN]),
        ]
    );
    $collectionPerson = mock($collectionPerson)
        ->shouldReceive('list')
        ->with(\Mockery::type(Caller::class), \Mockery::type(PaginatedFilter::class), \Mockery::type(Projection::class))
        ->andReturnUsing(
            function (Caller $caller, PaginatedFilter $filter, Projection $projection) use ($collectionPerson, $personsRecords) {
                if ($filter->getConditionTree()) {
                    $personsRecords = $filter->getConditionTree()->apply($personsRecords, $collectionPerson, 'Europe/Paris');
                }
                if ($filter->getSort()) {
                    $personsRecords = $filter->getSort()->apply($personsRecords);
                }

                return $projection->apply($personsRecords)->toArray();
            }
        )
        ->shouldReceive('aggregate')
        ->with(\Mockery::type(Caller::class), \Mockery::type(Filter::class), \Mockery::type(Aggregation::class))
        ->andReturnUsing(
            function (Caller $caller, Filter $filter, Aggregation $aggregation) use ($personsRecords) {
                return $aggregation->apply($personsRecords, 'Europe/Paris');
            }
        )->getMock();


    $datasource->addCollection($collectionPicture);
    $datasource->addCollection($collectionPassport);
    $datasource->addCollection($collectionPerson);
    buildAgent($datasource);
    $datasourceDecorator = new DatasourceDecorator($datasource, RelationCollection::class);
    $datasourceDecorator->build();

    return ['datasource' => $datasource, 'datasourceDecorator' => $datasourceDecorator];
}

test('OneToOne - addRelation() should throw with a non existent fk', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToOne',
                'foreignCollection' => 'Passport',
                'originKey'         => '__nonExisting__',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Passport.__nonExisting__');
});

test('OneToOne - addRelation() should throw when In is not supported by the fk in the target when missing operators', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToOne',
                'foreignCollection' => 'Passport',
                'originKey'         => 'pictureId',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Column does not support the In operator: Passport.pictureId');
});

test('OneToOne - addRelation() should throw when there is a given originKeyTarget that does not match the target type', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToOne',
                'foreignCollection' => 'Passport',
                'originKey'         => 'ownerId',
                'originKeyTarget'   => 'name',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Types from Passport.ownerId and Person.name do not match');
});

test('OneToOne - addRelation() should register the relation when there is a given originKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToOne',
                'foreignCollection' => 'Passport',
                'originKey'         => 'ownerId',
                'originKeyTarget'   => 'id',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('OneToOne - addRelation() should register the relation when there is not a given originKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToOne',
                'foreignCollection' => 'Passport',
                'originKey'         => 'ownerId',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('OneToMany - addRelation() should throw when there is a given originKeyTarget that does not match the target type', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToMany',
                'foreignCollection' => 'Passport',
                'originKey'         => 'ownerId',
                'originKeyTarget'   => 'name',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Types from Passport.ownerId and Person.name do not match');
});

test('OneToMany - addRelation() should register the relation when there is a given originKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToMany',
                'foreignCollection' => 'Passport',
                'originKey'         => 'ownerId',
                'originKeyTarget'   => 'id',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('OneToMany - addRelation() should register the relation when there is not a given originKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        $relationCollection->addRelation(
            'passport',
            [
                'type'              => 'OneToMany',
                'foreignCollection' => 'Passport',
                'originKey'         => 'ownerId',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('ManyToOne - addRelation() should throw with a non existent collection', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Passport');

    expect(
        static fn () => $relationCollection->addRelation(
            'someName',
            [
                'type'              => 'ManyToOne',
                'foreignCollection' => '__nonExisting__',
                'foreignKey'        => 'ownerId',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Collection __nonExisting__ not found');
});

test('ManyToOne - addRelation() should throw with a non existent fk', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Passport');

    expect(
        static fn () => $relationCollection->addRelation(
            'owner',
            [
                'type'              => 'ManyToOne',
                'foreignCollection' => 'Person',
                'foreignKey'        => '__nonExisting__',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Passport.__nonExisting__');
});

test('ManyToOne - addRelation() should throw when In is not supported by the fk in the target when missing operators', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Passport');

    expect(
        static fn () => $relationCollection->addRelation(
            'owner',
            [
                'type'              => 'ManyToOne',
                'foreignCollection' => 'Person',
                'foreignKey'        => 'pictureId',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Column does not support the In operator: Passport.pictureId');
});

test('ManyToOne - addRelation() should register the relation when there is a given foreignKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Passport');

    expect(
        $relationCollection->addRelation(
            'owner',
            [
                'type'              => 'ManyToOne',
                'foreignCollection' => 'Person',
                'foreignKey'        => 'ownerId',
                'foreignKeyTarget'  => 'id',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('ManyToOne - addRelation() should register the relation when there is not a given foreignKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Passport');

    expect(
        $relationCollection->addRelation(
            'owner',
            [
                'type'              => 'ManyToOne',
                'foreignCollection' => 'Person',
                'foreignKey'        => 'ownerId',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('ManyToMany - addRelation() should throw with a non existent though collection', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'persons',
            [
                'type'              => 'ManyToMany',
                'throughTable'      => '',
                'foreignCollection' => 'Passport',
                'foreignKey'        => 'ownerId',
                'originKey'         => 'ownerId',
                'throughCollection' => '__nonExisting__',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Collection __nonExisting__ not found');
});

test('ManyToMany - addRelation() should throw with a non existent originKey', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'persons',
            [
                'type'              => 'ManyToMany',
                'throughTable'      => '',
                'foreignCollection' => 'Passport',
                'foreignKey'        => 'ownerId',
                'originKey'         => '__nonExisting__',
                'throughCollection' => 'Passport',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Passport.__nonExisting__');
});

test('ManyToMany - addRelation() should throw with a non existent fk', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'persons',
            [
                'type'              => 'ManyToMany',
                'throughTable'      => '',
                'foreignCollection' => 'Passport',
                'foreignKey'        => '__nonExisting__',
                'originKey'         => 'ownerId',
                'throughCollection' => 'Passport',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Column not found: Passport.__nonExisting__');
});

test('ManyToMany - addRelation() should throw when there is a given originKeyTarget that does not match the target type', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        static fn () => $relationCollection->addRelation(
            'persons',
            [
                'type'              => 'ManyToMany',
                'throughTable'      => '',
                'foreignCollection' => 'Passport',
                'foreignKey'        => 'ownerId',
                'foreignKeyTarget'  => 'id',
                'originKey'         => 'ownerId',
                'originKeyTarget'   => 'name',
                'throughCollection' => 'Passport',
            ]
        )
    )->toThrow(ForestException::class, '🌳🌳🌳 Types from Passport.ownerId and Person.name do not match.');
});

test('ManyToMany - addRelation() should register the relation when there are a given originKeyTarget and foreignKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        $relationCollection->addRelation(
            'persons',
            [
                'type'              => 'ManyToMany',
                'throughTable'      => '',
                'foreignCollection' => 'Passport',
                'foreignKey'        => 'ownerId',
                'foreignKeyTarget'  => 'id',
                'originKey'         => 'ownerId',
                'originKeyTarget'   => 'id',
                'throughCollection' => 'Passport',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('ManyToMany - addRelation() should register the relation when there are not a given originKeyTarget and foreignKeyTarget', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');

    expect(
        $relationCollection->addRelation(
            'persons',
            [
                'type'              => 'ManyToMany',
                'throughTable'      => '',
                'foreignCollection' => 'Passport',
                'foreignKey'        => 'ownerId',
                'originKey'         => 'ownerId',
                'throughCollection' => 'Passport',
            ]
        )
    )->not()->toThrow(ForestException::class);
});

test('emulated projection should fetch fields from a many to one relation', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Passport');
    $relationCollection->addRelation('owner', [
        'type'              => 'ManyToOne',
        'foreignCollection' => 'Person',
        'foreignKey'        => 'ownerId',
    ]);

    $records = $relationCollection->list(
        QueryStringParser::parseCaller(Request::createFromGlobals()),
        new PaginatedFilter(),
        new Projection(['id', 'owner:name'])
    );

    expect($records)->toEqual(
        [
            ['id' => 101, 'owner' => ['name' => 'Mae S. Waldron']],
            ['id' => 102, 'owner' => ['name' => 'Sharon J. Whalen']],
            ['id' => 103, 'owner' => null],
        ]
    );
});

test('emulated projection should fetch fields from a one to one relation', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');
    $relationCollection->addRelation('passport', [
        'type'              => 'OneToOne',
        'foreignCollection' => 'Passport',
        'originKey'         => 'ownerId',
        'originKeyTarget'   => 'otherId',
    ]);

    $records = $relationCollection->list(
        QueryStringParser::parseCaller(Request::createFromGlobals()),
        new PaginatedFilter(),
        new Projection(['id', 'name', 'passport:issueDate'])
    );

    expect($records)->toEqual(
        [
            ['id' => 201, 'name' => 'Sharon J. Whalen', 'passport' => ['issueDate' => '2017-01-01']],
            ['id' => 202, 'name' => 'Mae S. Waldron', 'passport' => ['issueDate' => '2010-01-01']],
            ['id' => 203, 'name' => 'Joseph P. Rodriguez', 'passport' => null],
        ]
    );
});

test('emulated projection should fetch fields from a one to many relation', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');
    $relationCollection->addRelation('passport', [
        'type'              => 'OneToMany',
        'foreignCollection' => 'Passport',
        'originKey'         => 'ownerId',
        'originKeyTarget'   => 'otherId',
    ]);

    $records = $relationCollection->list(
        QueryStringParser::parseCaller(Request::createFromGlobals()),
        new PaginatedFilter(),
        new Projection(['id', 'name', 'passport:issueDate'])
    );

    expect($records)->toEqual(
        [
            ['id' => 201, 'name' => 'Sharon J. Whalen', 'passport' => ['issueDate' => '2017-01-01']],
            ['id' => 202, 'name' => 'Mae S. Waldron', 'passport' => ['issueDate' => '2010-01-01']],
            ['id' => 203, 'name' => 'Joseph P. Rodriguez', 'passport' => null],
        ]
    );
});

test('emulated projection should fetch fields from a many to many relation', function () {
    /** @var DatasourceDecorator $datasourceDecorator */
    ['datasourceDecorator' => $datasourceDecorator] = factoryRelationCollection();
    /** @var RelationCollection $relationCollection */
    $relationCollection = $datasourceDecorator->getCollection('Person');
    $relationCollection->addRelation('persons', [
        'type'              => 'ManyToMany',
        'foreignCollection' => 'Person',
        'foreignKey'        => 'ownerId',
        'originKey'         => 'ownerId',
        'throughCollection' => 'Passport',
        'throughTable'      => '',
        'originKeyTarget'   => 'otherId',
        'foreignKeyTarget'  => 'id',
    ]);

    $records = $relationCollection->list(
        QueryStringParser::parseCaller(Request::createFromGlobals()),
        new PaginatedFilter(),
        new Projection(['id', 'name', 'persons:name'])
    );

    expect($records)->toEqual(
        [
            ['id' => 201, 'name' => 'Sharon J. Whalen', 'persons' => null],
            ['id' => 202, 'name' => 'Mae S. Waldron', 'persons' => null],
            ['id' => 203, 'name' => 'Joseph P. Rodriguez', 'persons' => null],
        ]
    );
});
