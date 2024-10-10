<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\BaseLayoutElement;

it('can be instantiated with a component', function () {
    $element = new BaseLayoutElement('Foo');

    expect($element->getComponent())->toBe('Foo');
    expect($element->getType())->toBe('Layout');
});

it('can set and get the component', function () {
    $element = new BaseLayoutElement('Foo');
    $element->setComponent('Bar');

    expect($element->getComponent())->toBe('Bar');
});

it('returns an array representation excluding type', function () {
    $element = new BaseLayoutElement('Foo');
    $element->setComponent('Bar');

    $array = $element->toArray();

    expect($array)->toBeArray()
        ->and($array)->not->toHaveKey('type')
        ->and($array)->toHaveKey('component')
        ->and($array['component'])->toBe('Bar');
});

it('can dynamically set and get properties using magic methods', function () {
    $element = new BaseLayoutElement('Foo');

    $element->__set('extraField', 'Extra Value');
    expect($element->__get('extraField'))->toBe('Extra Value');
    expect($element->__get('extraField') !== null)->toBeTrue();
    expect($element->__get('nonExistentField') !== null)->toBeFalse();
});

it('returns null for non-existent properties using __get', function () {
    $element = new BaseLayoutElement('Foo');

    expect($element->__get('nonExistentField'))->toBeNull();
});
