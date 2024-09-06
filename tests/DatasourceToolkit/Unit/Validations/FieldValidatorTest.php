<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\OneToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\PolymorphicManyToOneSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\FieldValidator;
use ForestAdmin\AgentPHP\Tests\TestCase;

describe('validate()', function () {
    $before = static function (TestCase $testCase) {
        $datasource = new Datasource();
        $collectionCars = new Collection($datasource, 'cars');
        $collectionCars->addFields(
            [
                'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
                'owner' => new OneToOneSchema(
                    originKey: 'id',
                    originKeyTarget: 'id',
                    foreignCollection: 'owner',
                ),
                'commentable' => new PolymorphicManyToOneSchema(
                    foreignKeyTypeField: 'commentableType',
                    foreignKey: 'commentableId',
                    foreignKeyTargets: ['Car' => 'id'],
                    foreignCollections: ['Car']
                ),
            ]
        );
        $collectionOwners = new Collection($datasource, 'owner');
        $collectionOwners->addFields(
            [
                'id'      => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
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

        $testCase->buildAgent($datasource);
        $testCase->bucket['collection'] = $collectionCars;
    };

    test('should not throw if the field exist on the collection', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, 'id'));
    })->expectNotToPerformAssertions();

    test('should throw if the field does not exists', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, '__not_defined'));
    })->throws(ForestException::class, '🌳🌳🌳 Column not found: cars.__not_defined');

    test('should throw if the relation does not exists', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, '__not_defined:id'));
    })->throws(ForestException::class, '🌳🌳🌳 Relation not found: cars.__not_defined');

    test('should throw if the field is not of column type', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, 'owner'));
    })->throws(ForestException::class, '🌳🌳🌳 Unexpected field type: cars.owner (found OneToOne expected \'Column\')');

    test('should throw when the suffix is different of "Asterisk character"', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, 'commentable:foo'));
    })->throws(ForestException::class, '🌳🌳🌳 Unexpected nested field foo under generic relation: cars.commentable');

    test('should throw when the requested field is of type column', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, 'id:address'));
    })->throws(ForestException::class, '🌳🌳🌳 Unexpected field type: cars.id (found Column)');

    test('should validate field when the value is an array', function () use ($before) {
        $before($this);
        $collection = $this->bucket['collection'];
        expect(FieldValidator::validate($collection, 'id', ['foo', 'bar']));
    })->throws(ForestException::class, '🌳🌳🌳 Wrong type for id: foo. Expects Number');
});

describe('validateValue()', function () {
    test('on field of type boolean with valid value should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::BOOLEAN);
        expect(FieldValidator::validateValue('boolean', $column, true));
    })->expectNotToPerformAssertions();

    test('on field of type boolean invalid value type should throw error', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::BOOLEAN);
        expect(FieldValidator::validateValue('boolean', $column, 'not a boolean'));
    })->throws(ForestException::class, '🌳🌳🌳 Wrong type for boolean: not a boolean. Expects Boolean');

    test('on field of type string with valid value should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::STRING);
        expect(FieldValidator::validateValue('string', $column, 'test'));
    })->expectNotToPerformAssertions();

    test('on field of type string invalid value type should throw error', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::STRING);
        expect(FieldValidator::validateValue('string', $column, 1));
    })->throws(ForestException::class, '🌳🌳🌳 Wrong type for string: 1. Expects String');

    test('on field of type number with valid value should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::NUMBER);
        expect(FieldValidator::validateValue('number', $column, 1));
    })->expectNotToPerformAssertions();

    test('on field of type date|dateonly|timeonly with valid value (string) should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::DATE);
        expect(FieldValidator::validateValue('date', $column, '2022-01-13T17:16:04.000Z'));
    })->expectNotToPerformAssertions();

    test('on field of type date|dateonly|timeonly with valid value (date) should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::DATE);
        expect(FieldValidator::validateValue('date', $column, new DateTime()));
    })->expectNotToPerformAssertions();

    test('on field of type date|dateonly|timeonly invalid value type should throw error', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::DATE);
        expect(FieldValidator::validateValue('date', $column, 'definitely-not-a-date'));
    })->throws(ForestException::class, '🌳🌳🌳 Wrong type for date: definitely-not-a-date. Expects Date');

    test('on field of type enum with valid value should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::ENUM, enumValues: ['a', 'b', 'c']);
        expect(FieldValidator::validateValue('enum', $column, 'a'));
    })->expectNotToPerformAssertions();

    test('on field of type enum invalid value type should throw error', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::ENUM, enumValues: ['a', 'b', 'c']);
        expect(FieldValidator::validateValue('enum', $column, 'd'));
    })->throws(ForestException::class, '🌳🌳🌳 The given enum value(s) d is not listed in [a,b,c]');

    test('on field of type json with valid value (string) should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::JSON);
        expect(FieldValidator::validateValue('json', $column, '{"foo": "bar"}'));
    })->expectNotToPerformAssertions();

    test('with a failed declaration of json should also be a valid a json', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::JSON);
        expect(fn () => FieldValidator::validateValue('json', $column, '{not: "a:" valid json'));
    })->expectNotToPerformAssertions();

    test('on field of type uuid with valid value (uuid v1) should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::UUID);
        expect(FieldValidator::validateValue('uuid', $column, 'a7147d1c-7d44-11ec-90d6-0242ac120003'));
    })->expectNotToPerformAssertions();

    test('on field of type uuid with valid value (uuid v4) should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::UUID);
        expect(FieldValidator::validateValue('uuid', $column, '05db90e8-6e72-4278-888d-9b127c91470e'));
    })->expectNotToPerformAssertions();

    test('on field of type uuid invalid value type should throw error', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::UUID);
        expect(FieldValidator::validateValue('uuid', $column, 'not-a-valid-uuid'));
    })->throws(ForestException::class, '🌳🌳🌳 Wrong type for uuid: not-a-valid-uuid. Expects Uuid');

    test('on field of type point with valid value should not throw', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::POINT);
        expect(FieldValidator::validateValue('point', $column, '1,2'));
    })->expectNotToPerformAssertions();

    test('on field of type point invalid value type should throw error', function () {
        $column = new ColumnSchema(columnType: PrimitiveType::POINT);
        expect(FieldValidator::validateValue('point', $column, 'd,a'));
    })->throws(ForestException::class, '🌳🌳🌳 Wrong type for point: d,a. Expects Point');
});
