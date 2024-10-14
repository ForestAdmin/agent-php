<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;

describe('getInverseRelation() when inverse relations is missing', function () {
    beforeEach(function () {
        $this->bucket['dynamicField'] = new DynamicField(
            type: FieldType::STRING,
            label: 'amount X10',
            description: 'test',
            isRequired: true,
            isReadOnly: true,
            if: fn () => 'ok',
            value: '1',
            defaultValue: '10',
            collectionName: 'Product',
            enumValues: null,
        );
    });

    test('__set() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        $dynamicField->__set('isRequired', false);

        expect($dynamicField->isRequired())->toBeFalse();
    });

    test('__get() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->__get('isRequired'))->toBeTrue();
    });

    test('__isset() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->__isset('isRequired'))->toBeTrue();
    });

    test('getType() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getType())->toEqual(FieldType::STRING);
    });

    test('setType() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setType(FieldType::NUMBER);

        expect($dynamicField->getType())->toEqual(FieldType::NUMBER);
    });

    test('getLabel() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getLabel())->toEqual('amount X10');
    });

    test('setLabel() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setLabel('test');

        expect($dynamicField->getLabel())->toEqual('test');
    });

    test('getDescription() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getDescription())->toEqual('test');
    });

    test('setDescription() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setDescription('test');

        expect($dynamicField->getDescription())->toEqual('test');
    });

    test('isRequired() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->isRequired())->toEqual(true);
    });

    test('setIsRequired() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setIsRequired(false);

        expect($dynamicField->isRequired())->toBeFalse();
    });

    test('isReadOnly() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->isReadOnly())->toEqual(true);
    });

    test('setIsReadOnly() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setIsReadOnly(false);

        expect($dynamicField->isReadOnly())->toBeFalse();
    });

    test('getIf() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getIf())->toEqual(fn () => 'ok');
    });

    test('setIf() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setIf(null);

        expect($dynamicField->getIf())->toBeNull();
    });

    test('getValue() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getValue())->toEqual('1');
    });

    test('setValue() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setValue('20');

        expect($dynamicField->getValue())->toEqual('20');
    });

    test('getDefaultValue() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getDefaultValue())->toEqual('10');
    });

    test('setDefaultValue() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setDefaultValue('20');

        expect($dynamicField->getDefaultValue())->toEqual('20');
    });

    test('getCollectionName() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getCollectionName())->toEqual('Product');
    });

    test('setCollectionName() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setCollectionName('Book');

        expect($dynamicField->getCollectionName())->toEqual('Book');
    });

    test('getEnumValues() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->getEnumValues())->toBeNull();
    });

    test('setEnumValues() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];
        $dynamicField->setEnumValues(['foo', 'bar']);

        expect($dynamicField->getEnumValues())->toEqual(['foo', 'bar']);
    });

    test('isStatic() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->isStatic())->toBeFalse();
    });

    test('keys() should work', function () {
        $dynamicField = $this->bucket['dynamicField'];

        expect($dynamicField->keys())->toEqual([
            'type',
            'label',
            'id',
            'description',
            'isRequired',
            'isReadOnly',
            'if',
            'value',
            'defaultValue',
            'collectionName',
            'enumValues',
        ]);
    });
});
