<?php

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendValidation;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

test('convertValidationList() should return an empty array', function () {
    expect(FrontendValidation::convertValidationList([]))->toBeEmpty();
});

test('convertValidationList() should work with bounds', function () {
    expect(
        FrontendValidation::convertValidationList(
            [
                ['operator' => Operators::LESS_THAN, 'value' => 34],
                ['operator' => Operators::GREATER_THAN, 'value' => 60],
            ]
        )
    )->toEqual(
        [
            [
                'message' => "Failed validation rule: Less_Than(34)",
                'type'    => FrontendValidation::OPERATOR_VALIDATION_TYPE_MAP[Operators::LESS_THAN],
                'value'   => 34,
            ],

            [
                'message' => "Failed validation rule: Greater_Than(60)",
                'type'    => FrontendValidation::OPERATOR_VALIDATION_TYPE_MAP[Operators::GREATER_THAN],
                'value'   => 60,
            ],
        ]
    );
});

test('convertValidationList() should skip validation which cannot be translated', function () {
    expect(FrontendValidation::convertValidationList([['operator' => Operators::PREVIOUS_QUARTER]]))->toBeEmpty();
});
