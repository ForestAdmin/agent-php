<?php

namespace Tests\Unit\ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\LayoutElement;

it('can instantiate a LayoutElement with a valid component', function () {
    $layoutElement = new LayoutElement(component: 'Row');

    expect($layoutElement->getComponent())->toBe('Row')
        ->and($layoutElement->getIf())->toBeNull();
});

it('can set and get the if condition', function () {
    $condition = fn () => true;

    $layoutElement = new LayoutElement(component: 'Row', if: $condition);

    expect($layoutElement->getIf())->toBe($condition);
});

it('can update the if condition', function () {
    $layoutElement = new LayoutElement(component: 'Row');

    $newCondition = fn () => false;
    $layoutElement->setIf($newCondition);

    expect($layoutElement->getIf())->toBe($newCondition);
});

it('can dynamically set and get component property', function () {
    $layoutElement = new LayoutElement(component: 'Row');
    $if = fn () => 'some condition';

    $layoutElement->setIf($if);
    expect($layoutElement->getIf())->toBe($if);
});
