<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\Rules;
use ForestAdmin\AgentPHP\Tests\DatasourceToolkit\Factories\ConditionTreeUnknown;

use function ForestAdmin\cache;

dataset('ConditionTreeCollection', static function () {
    $datasource = new Datasource();
    yield $collectionCars = new Collection($datasource, 'cars');
    $collectionCars->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'model'     => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: Rules::BASE_OPERATORS),
            'reference' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: ['Contains', 'GreaterThan']),
            'owner'     => new OneToOneSchema(
                originKey: 'id',
                originKeyTarget: 'id',
                foreignCollection: 'owner',
            ),
        ]
    );
    $collectionOwners = new Collection($datasource, 'owner');
    $collectionOwners->addFields(
        [
            'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: Rules::BASE_OPERATORS, isPrimaryKey: true),
            'name'    => new ColumnSchema(columnType: PrimitiveType::STRING),
            'address' => new OneToOneSchema(
                originKey: 'id',
                originKeyTarget: 'id',
                foreignCollection: 'address'
            ),
        ]
    );

    $datasource->addCollection($collectionCars);
    $datasource->addCollection($collectionOwners);

    $options = [
        'projectDir' => sys_get_temp_dir(), // only use for cache
    ];
    new AgentFactory($options);
    cache('datasource', $datasource);
});

test('validate() should throw an error with invalid type', function ($collection) {
    expect(ConditionTreeValidator::validate(new ConditionTreeUnknown(), $collection));
})->throws(ForestException::class, 'Unexpected condition tree type')
    ->with('ConditionTreeCollection');

test('validate() should throw an error with invalid aggregator on branch', function ($collection) {
    $conditionTree = new ConditionTreeBranch(
        'and',// should be 'And'
        [],
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 The given aggregator and is not supported. The supported values are: [\'Or\', \'And\']'
)->with('ConditionTreeCollection');

test('validate() should throw an error when the field(s) does not exist in the schema', function ($collection) {
    $conditionTree = new ConditionTreeLeaf(
        field: 'fieldDoesNotExistInSchema',
        operator: 'Equal',
        value: 'targetValue',
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 Column not found cars.fieldDoesNotExistInSchema'
)->with('ConditionTreeCollection');

test('validate() should not throw an error when there are relations in the datasource', function ($collection) {
    $conditionTree = new ConditionTreeLeaf(
        field: 'owner:id',
        operator: 'Equal',
        value: 1,
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions()->with('ConditionTreeCollection');

test('validate() should throw an error when a field does not exist when there are several fields', function ($collection) {
    $conditionTree = new ConditionTreeBranch(
        aggregator: 'Or',
        conditions: [
            new ConditionTreeLeaf(
                field: 'model',
                operator: 'Equal',
                value: 'modelValue'
            ),
            new ConditionTreeLeaf(
                field: 'fieldDoesNotExistInSchema',
                operator: 'Equal',
                value: 'modelValue'
            ),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 Column not found cars.fieldDoesNotExistInSchema'
)->with('ConditionTreeCollection');

test('validate() should not throw an error when the field(s) exist', function ($collection) {
    $conditionTree = new ConditionTreeLeaf(
        field: 'model',
        operator: 'Equal',
        value: 'modelValue',
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions()->with('ConditionTreeCollection');

test('validate() should throw an error when the field has an operator incompatible with the schema type', function ($collection) {
    $conditionTree = new ConditionTreeLeaf(
        field: 'reference',
        operator: 'Contains',
        value: 'modelValue'
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 The given operator Contains is not allowed with the columnType schema: Number. The allowed types are: Blank,Equal,Missing,NotEqual,Present,In,NotIn,IncludesAll,GreaterThan,LessThan'
)->with('ConditionTreeCollection');

test('validate() should throw an error when the operator is incompatible with the given value', function ($collection) {
    $conditionTree = new ConditionTreeLeaf(
        field: 'reference',
        operator: 'GreaterThan',
        value: null
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳 The allowed types of the field value are: Number,Timeonly'
)->with('ConditionTreeCollection');


test('validate() should throw an error when the value is not compatible with the column type', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'model',
        operator: 'In',
        value: [1, 2, 3]
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'model' => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: ['In']),
        ]
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 Wrong type for model: [1,2,3]. Expects String,ArrayOfString,Null'
);

test('validate() should not throw an error when a list of uuid is given when the field is an UUID', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'uuidField',
        operator: 'In',
        value: ['2d162303-78bf-599e-b197-93590ac3d315', '2d162303-78bf-599e-b197-93590ac3d315']
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'uuidField' => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: ['In']),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions();

test('validate() should throw an error when at least one uuid is malformed when the field is an UUID', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'uuidField',
        operator: 'In',
        value: ['2d162303-78bf-599e-b197-93590ac3d315', '2d162303-78bf-599e-b197-93590ac3d33534315']
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'uuidField' => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: ['In']),
        ]
    );

    // if on of value is not uuid then type is array of null ???

//    dd(ConditionTreeValidator::validate($conditionTree, $collection));
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 Wrong type for uuidField: [2d162303-78bf-599e-b197-93590ac3d315,2d162303-78bf-599e-b197-93590ac3d33534315]. Expects Uuid'
);

test('validate() should throw an error when the field value is not a valid enum when the field is an enum', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'enumField',
        operator: 'Equal',
        value: 'aRandomValue'
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'enumField' => new ColumnSchema(
                columnType: PrimitiveType::ENUM,
                filterOperators: ['Equal'],
                enumValues: ['anAllowedValue']
            ),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 The given enum value(s) aRandomValue is not listed in [anAllowedValue]'
);

test('validate() should throw an error when the at least one field value is not a valid enum when the field is an enum', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'enumField',
        operator: 'Equal',
        value: ['allowedValue', 'aRandomValue']
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'enumField' => new ColumnSchema(
                columnType: PrimitiveType::ENUM,
                filterOperators: ['Equal'],
                enumValues: ['anAllowedValue']
            ),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 The given enum value(s) [allowedValue,aRandomValue] is not listed in [anAllowedValue]'
);

test('validate() should not throw an error when all enum values are allowed when the field is an enum', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'enumField',
        operator: 'Equal',
        value: ['allowedValue', 'otherAllowedValue']
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'enumField' => new ColumnSchema(
                columnType: PrimitiveType::ENUM,
                filterOperators: ['Equal'],
                enumValues: ['allowedValue', 'otherAllowedValue']
            ),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions();

test('validate() should not throw an error when the filter value is well formatted when the field is a Point', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'pointField',
        operator: 'Equal',
        value: '-80,20'
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'pointField' => new ColumnSchema(
                columnType: PrimitiveType::POINT,
                filterOperators: ['Equal'],
            ),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions();

test('validate() should throw an error when the field is a Point and the value is not well formatted', function () {
    $conditionTree = new ConditionTreeLeaf(
        field: 'pointField',
        operator: 'Equal',
        value: '-80, 20, 90'
    );
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'pointField' => new ColumnSchema(
                columnType: PrimitiveType::POINT,
                filterOperators: ['Equal'],
            ),
        ]
    );
    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 Wrong type for pointField: -80, 20, 90. Expects Point,Null'
);

test('validate() date operator when it does not support a value', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'dateField'       => new ColumnSchema(
                columnType: PrimitiveType::DATE,
                filterOperators: Rules::getAllowedOperatorsForColumnType(PrimitiveType::DATE),
            ),
        ]
    );

    $operators = [
        'Blank',
        'Missing',
        'Present',
        'Yesterday',
        'Today',
        'PreviousQuarter',
        'PreviousYear',
        'PreviousMonth',
        'PreviousWeek',
        'Past',
        'Future',
        'PreviousWeekToDate',
        'PreviousMonthToDate',
        'PreviousQuarterToDate',
        'PreviousYearToDate',
    ];

    expect($operators)->each(
        function ($operator) use ($collection) {
            // should throw an error when a date is given
            $conditionTreeThrow = new ConditionTreeLeaf(
                field: 'dateField',
                operator: $operator->value,
                value: Date('Y-m-d')
            );
            expect(fn () => ConditionTreeValidator::validate($conditionTreeThrow, $collection))
                ->toThrow(ForestException::class, '🌳🌳🌳 The allowed types of the field value are: Null');
        }
    )
        ->and($operators)->each(
            function ($operator) use ($collection) {
                // should not throw an error when the value is empty
                $conditionTree = new ConditionTreeLeaf(
                    field: 'dateField',
                    operator: $operator->value,
                    value: null
                );
                ConditionTreeValidator::validate($conditionTree, $collection);
            }
        )->not->toThrow(ForestException::class);
});

test('validate() date operator when it support only a number', function () {
    $collection = new Collection(new Datasource(), 'cars');
    $collection->addFields(
        [
            'id'              => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'dateField'       => new ColumnSchema(
                columnType: PrimitiveType::DATE,
                filterOperators: Rules::getAllowedOperatorsForColumnType(PrimitiveType::DATE),
            ),
        ]
    );

    $operators = [
        'PreviousXDays',
        'BeforeXHoursAgo',
        'AfterXHoursAgo',
        'PreviousXDaysToDate',
    ];

    expect($operators)->each(
        function ($operator) use ($collection) {
            // should throw an error when a date is given
            $conditionTreeThrow = new ConditionTreeLeaf(
                field: 'dateField',
                operator: $operator->value,
                value: Date('Y-m-d')
            );
            expect(fn () => ConditionTreeValidator::validate($conditionTreeThrow, $collection))
                ->toThrow(ForestException::class, '🌳🌳🌳 The allowed types of the field value are: Number');
        }
    )
        ->and($operators)->each(
            function ($operator) use ($collection) {
                // should not throw an error when the value is empty
                $conditionTree = new ConditionTreeLeaf(
                    field: 'dateField',
                    operator: $operator->value,
                    value: 10
                );
                ConditionTreeValidator::validate($conditionTree, $collection);
            }
        )->not->toThrow(ForestException::class);
});
