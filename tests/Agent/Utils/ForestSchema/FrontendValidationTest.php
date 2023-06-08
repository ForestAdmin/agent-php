<?php

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendValidation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;

test('convertValidationList() shoud work with null validation', function () {
    $column = new ColumnSchema(columnType: 'String');

    expect(FrontendValidation::convertValidationList($column))->toBeEmpty();
});

test('convertValidationList() should work with empty validation', function () {
    $column = new ColumnSchema(columnType: 'String', validation: []);

    expect(FrontendValidation::convertValidationList($column))->toBeEmpty();
});

test('convertValidationList() should work with supported handlers (strings)', function () {
    $column = new ColumnSchema(
        columnType: 'Number',
        validation: [
            ['operator' => Operators::PRESENT],
            ['operator' => Operators::LESS_THAN, 'value' => 34],
            ['operator' => Operators::GREATER_THAN, 'value' => 60],
        ]
    );

    expect(FrontendValidation::convertValidationList($column))->toEqual(
        [
            [
                'message' => 'Field is required',
                'type'    => 'is present',
            ],
            [
                'message' => 'Value must be lower than 34',
                'type'    => 'is less than',
                'value'   => 34,
            ],
            [
                'message' => 'Value must be greater than 60',
                'type'    => 'is greater than',
                'value'   => 60,
            ],
        ]
    );
});

test('convertValidationList() should work with supported handlers (date)', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'Date',
            validation: [
                ['operator' => Operators::BEFORE, 'value' => '2010-01-01T00:00:00Z'],
                ['operator' => Operators::AFTER, 'value' => '2010-01-01T00:00:00Z'],
            ]
        )
    );

    expect($validationList)->toEqual(
        [
            [
                'message' => 'Value must be before 2010-01-01T00:00:00Z',
                'type'    => 'is before',
                'value'   => '2010-01-01T00:00:00Z',
            ],
            [
                'message' => 'Value must be after 2010-01-01T00:00:00Z',
                'type'    => 'is after',
                'value'   => '2010-01-01T00:00:00Z',
            ],
        ]
    );
});

test('convertValidationList() should work with supported handlers (string)', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'Number',
            validation: [
                ['operator' => Operators::LONGER_THAN, 'value' => 34],
                ['operator' => Operators::SHORTER_THAN, 'value' => 60],
                ['operator' => Operators::CONTAINS, 'value' => 'abc'],
                ['operator' => Operators::MATCH, 'value' => '/abc/'],
            ]
        )
    );

    expect($validationList)->toEqual(
        [
            [
                'message' => 'Value must be longer than 34 characters',
                'type'    => 'is longer than',
                'value'   => 34,
            ],
            [
                'message' => 'Value must be shorter than 60 characters',
                'type'    => 'is shorter than',
                'value'   => 60,
            ],
            [
                'message' => "Value must contain 'abc'",
                'type'    => 'contains',
                'value'   => 'abc',
            ],
            [
                'message' => 'Value must match /abc/',
                'type'    => 'is like',
                'value'   => '/abc/',
            ],
        ]
    );
});

test('convertValidationList() should perform replacements (fake enum)', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'String',
            validation: [
                ['operator' => Operators::IN, 'value' => ['a', 'b', 'c']],
            ],
        )
    );

    expect($validationList)->toEqual(
        [
            [
                'message' => 'Value must match /a|b|c/g',
                'type'    => 'is like',
                'value'   => '/a|b|c/g',
            ],
        ]
    );
});

test('convertValidationList() should handle duplication', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'Number',
            validation: [
                ['operator' => Operators::PRESENT],
                ['operator' => Operators::PRESENT],
                ['operator' => Operators::LESS_THAN, 'value' => 34],
                ['operator' => Operators::LESS_THAN, 'value' => 40],
                ['operator' => Operators::GREATER_THAN, 'value' => 60],
                ['operator' => Operators::GREATER_THAN, 'value' => 80],
                ['operator' => Operators::GREATER_THAN, 'value' => 70],
                ['operator' => Operators::MATCH, 'value' => '/a/'],
                ['operator' => Operators::MATCH, 'value' => '/b/'],
            ],
        )
    );
    expect($validationList)->toBeArray()
        ->and($validationList)->toHaveLength(4)
        ->and(collect($validationList)->contains([
            'message' => 'Field is required',
            'type'    => 'is present',
        ]))->toBeTrue()
        ->and(collect($validationList)->contains([
            'message' => 'Field is required',
            'type'    => 'is present',
        ]))->toBeTrue()
        ->and(collect($validationList)->contains([
            'message' => 'Value must be greater than 80',
            'type'    => 'is greater than',
            'value'   => 80,
        ], ))->toBeTrue()
        ->and(collect($validationList)->contains([
            'message' => 'Value must match /^(?=a)(?=b).*$/',
            'type'    => 'is like',
            'value'   => '/^(?=a)(?=b).*$/',
        ]))->toBeTrue();
});

test('convertValidationList() should handle rule expansion (not in with null)', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'String',
            validation: [
                ['operator' => Operators::NOT_IN, 'value' => ['a', 'b', null]],
            ],
        )
    );

    expect($validationList)->toEqual([
        [
            'message' => 'Value must match /(?!a|b)/g',
            'type'    => 'is like',
            'value'   => '/(?!a|b)/g',
        ],
    ]);
});

test('convertValidationList() should skip validation which cannot be translated (depends on current time)', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'Date',
            validation: [
                ['operator' => Operators::PREVIOUS_QUARTER],
            ],
        )
    );

    expect($validationList)->toBeArray()
        ->and($validationList)->toHaveLength(0);
});

test('convertValidationList() should skip validation which cannot be translated (fake enum with null)', function () {
    $validationList = FrontendValidation::convertValidationList(
        new ColumnSchema(
            columnType: 'String',
            validation: [
                ['operator' => Operators::IN, 'value' => ['a', 'b', null]],
            ],
        )
    );

    expect($validationList)->toEqual([
        [
            'message' => 'Value must match /(a|b)/g',
            'type'    => 'is like',
            'value'   => '/(a|b)/g',
        ],
    ]);
});
