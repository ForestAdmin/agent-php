<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeBranch;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Nodes\ConditionTreeLeaf;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ConditionTreeValidator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\Rules;
use ForestAdmin\AgentPHP\Tests\DatasourceToolkit\Factories\ConditionTreeUnknown;

function conditionTreeCollectionValidation(): Collection
{
    $datasource = new Datasource();
    $collectionCars = new Collection($datasource, 'cars');
    $collectionCars->addFields(
        [
            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'model'     => new ColumnSchema(columnType: PrimitiveType::STRING, filterOperators: Rules::BASE_OPERATORS),
            'reference' => new ColumnSchema(columnType: PrimitiveType::NUMBER, filterOperators: [Operators::CONTAINS, Operators::GREATER_THAN]),
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
                foreignCollection: 'address',
            ),
        ]
    );

    $datasource->addCollection($collectionCars);
    $datasource->addCollection($collectionOwners);

    buildAgent($datasource);

    return $collectionCars;
}

test('validate() should throw an error with invalid type', function () {
    $collection = conditionTreeCollectionValidation();

    expect(ConditionTreeValidator::validate(new ConditionTreeUnknown(), $collection));
})->throws(ForestException::class, 'Unexpected condition tree type');

test('validate() should throw an error with invalid aggregator on branch', function () {
    $collection = conditionTreeCollectionValidation();
    $conditionTree = new ConditionTreeBranch(
        'and',// should be 'And'
        [],
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 The given aggregator and is not supported. The supported values are: [\'Or\', \'And\']'
);

test('validate() should throw an error when the field(s) does not exist in the schema', function () {
    $collection = conditionTreeCollectionValidation();
    $conditionTree = new ConditionTreeLeaf(
        field: 'fieldDoesNotExistInSchema',
        operator: 'Equal',
        value: 'targetValue',
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 Column not found cars.fieldDoesNotExistInSchema'
);

test('validate() should not throw an error when there are relations in the datasource', function () {
    $collection = conditionTreeCollectionValidation();
    $conditionTree = new ConditionTreeLeaf(
        field: 'owner:id',
        operator: 'Equal',
        value: 1,
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions();

test('validate() should throw an error when a field does not exist when there are several fields', function () {
    $collection = conditionTreeCollectionValidation();
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
);

test('validate() should not throw an error when the field(s) exist', function () {
    $collection = conditionTreeCollectionValidation();
    $conditionTree = new ConditionTreeLeaf(
        field: 'model',
        operator: 'Equal',
        value: 'modelValue',
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->expectNotToPerformAssertions();

test('validate() should throw an error when the field has an operator incompatible with the schema type', function () {
    $collection = conditionTreeCollectionValidation();
    $conditionTree = new ConditionTreeLeaf(
        field: 'reference',
        operator: 'Contains',
        value: 'modelValue'
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳🌳 The given operator Contains is not allowed with the columnType schema: Number. The allowed types are: Equal,Not_Equal,Present,Blank,Missing,In,Not_In,Includes_All,Greater_Than,Less_Than'
);

test('validate() should throw an error when the operator is incompatible with the given value', function () {
    $collection = conditionTreeCollectionValidation();
    $conditionTree = new ConditionTreeLeaf(
        field: 'reference',
        operator: Operators::GREATER_THAN,
        value: null
    );

    expect(ConditionTreeValidator::validate($conditionTree, $collection));
})->throws(
    ForestException::class,
    '🌳🌳 The allowed types of the field value are: Number,Timeonly'
);


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

//test('validate() should throw an error when at least one uuid is malformed when the field is an UUID', function () {
//    $conditionTree = new ConditionTreeLeaf(
//        field: 'uuidField',
//        operator: 'In',
//        value: ['2d162303-78bf-599e-b197-93590ac3d315', '2d162303-78bf-599e-b197-93590ac3d33534315']
//    );
//    $collection = new Collection(new Datasource(), 'cars');
//    $collection->addFields(
//        [
//            'id'        => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
//            'uuidField' => new ColumnSchema(columnType: PrimitiveType::UUID, filterOperators: ['In']),
//        ]
//    );
//
//    // if on of value is not uuid then type is array of null ???
//
////    dd(ConditionTreeValidator::validate($conditionTree, $collection));
//    expect(ConditionTreeValidator::validate($conditionTree, $collection));
//})->throws(
//    ForestException::class,
//    '🌳🌳🌳 Wrong type for uuidField: [2d162303-78bf-599e-b197-93590ac3d315,2d162303-78bf-599e-b197-93590ac3d33534315]. Expects Uuid'
//);

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
        Operators::BLANK,
        Operators::MISSING,
        Operators::PRESENT,
        Operators::YESTERDAY,
        Operators::TODAY,
        Operators::PREVIOUS_QUARTER,
        Operators::PREVIOUS_YEAR,
        Operators::PREVIOUS_MONTH,
        Operators::PREVIOUS_WEEK,
        Operators::PAST,
        Operators::FUTURE,
        Operators::PREVIOUS_WEEK_TO_DATE,
        Operators::PREVIOUS_MONTH_TO_DATE,
        Operators::PREVIOUS_QUARTER_TO_DATE,
        Operators::PREVIOUS_YEAR_TO_DATE,
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
        Operators::PREVIOUS_X_DAYS,
        Operators::BEFORE_X_HOURS_AGO,
        Operators::AFTER_X_HOURS_AGO,
        Operators::PREVIOUS_X_DAYS_TO_DATE,
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
