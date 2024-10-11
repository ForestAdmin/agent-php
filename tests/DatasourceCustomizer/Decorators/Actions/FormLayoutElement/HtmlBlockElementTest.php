<?php

namespace Tests\Unit\ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\HtmlBlockElement;

test('creates an instance with null if attribute', function () {
    $htmlBlockElement = new HtmlBlockElement(content: '<p>foo</p>');

    expect($htmlBlockElement->getIf())->toBeNull();
});

test('creates an instance with the expected component and content value', function () {
    $htmlBlockElement = new HtmlBlockElement(content: '<p>foo</p>');

    expect($htmlBlockElement->getComponent())->toEqual('HtmlBlock')
        ->and($htmlBlockElement->getContent())->toEqual('<p>foo</p>');
});

test('creates an instance with if attribute', function () {
    $closure = fn () => true;
    $htmlBlockElement = new HtmlBlockElement(content: '<p>foo</p>', if: $closure);

    expect($htmlBlockElement->getIf())->toEqual($closure);
});
