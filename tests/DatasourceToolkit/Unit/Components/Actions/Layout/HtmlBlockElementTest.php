<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\HtmlBlockElement;

test('Set the expected component and content value ', function () {
    $separator = new HtmlBlockElement(content: '<p>foo</p>');

    expect($separator->getComponent())->toEqual('HtmlBlock')
        ->and($separator->getContent())->toEqual('<p>foo</p>');
});
