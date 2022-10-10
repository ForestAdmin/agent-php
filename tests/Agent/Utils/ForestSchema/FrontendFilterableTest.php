<?php


use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\FrontendFilterable;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;

test('isFilterable() should return false with no operators', function () {
    expect(FrontendFilterable::isFilterable('String'))->toBeFalse();
});

test('isFilterable() should return true with only the relevant operators', function () {
    expect(
        FrontendFilterable::isFilterable(
            'String',
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
            'String',
            Operators::getAllOperators()
        )
    )->toBeTrue();
});

test('isFilterable() should return false with array and no operators', function () {
    expect(FrontendFilterable::isFilterable(['String']))->toBeFalse();
});

test('isFilterable() should return true with includeAll', function () {
    expect(
        FrontendFilterable::isFilterable(
            ['String'],
            ['Includes_All']
        )
    )->toBeTrue();
});

test('isFilterable() should return false with type Point', function () {
    expect(FrontendFilterable::isFilterable('Point'))->toBeFalse()
        ->and(FrontendFilterable::isFilterable('Point', Operators::getAllOperators()))->toBeFalse();
});

test('isFilterable() should return false with type nested types', function () {
    $types = ['firstName' => 'String', 'lastName' => 'String'];
    expect(FrontendFilterable::isFilterable($types))->toBeFalse();
});
