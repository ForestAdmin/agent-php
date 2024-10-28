<?php

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

test('isFilterable() should return true with only the relevant operators', function () {
    expect(
        FrontendFilterable::isFilterable(
            [
                Operators::EQUAL,
                Operators::NOT_EQUAL,
                Operators::PRESENT,
                Operators::BLANK,
                Operators::IN,
                Operators::STARTS_WITH,
                Operators::ENDS_WITH,
                Operators::CONTAINS,
                Operators::NOT_CONTAINS,]
        )
    )->toBeTrue();
});

test('isFilterable() should return true with all operators', function () {
    expect(
        FrontendFilterable::isFilterable(
            Operators::getAllOperators()
        )
    )->toBeTrue();
});

test('isFilterable() should return true with includeAll', function () {
    expect(
        FrontendFilterable::isFilterable(
            ['Includes_All']
        )
    )->toBeTrue();
});

test('isFilterable() should return false when there is no operators', function () {
    expect(FrontendFilterable::isFilterable([]))->toBeFalse();
});
