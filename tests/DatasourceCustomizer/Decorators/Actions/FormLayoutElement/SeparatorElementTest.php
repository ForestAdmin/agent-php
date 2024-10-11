<?php

namespace Tests\Unit\ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\SeparatorElement;

test('creates an instance with null if attribute', function () {
    $separatorElement = new SeparatorElement();

    expect($separatorElement->getIf())->toBeNull();
});

test('creates an instance with expected component value', function () {
    $separatorElement = new SeparatorElement();

    expect($separatorElement->getComponent())->toEqual('Separator');
});

test('creates an instance with if attribute', function () {
    $closure = fn () => true;
    $separatorElement = new SeparatorElement(if: $closure);

    expect($separatorElement->getIf())->toEqual($closure);
});
