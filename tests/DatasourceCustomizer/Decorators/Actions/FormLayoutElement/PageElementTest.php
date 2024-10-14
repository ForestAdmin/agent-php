<?php

namespace Tests\Unit\ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\LayoutElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\PageElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

it('can instantiate a PageElement with valid elements', function () {
    $element1 = mock(LayoutElement::class)->makePartial();
    $element1->shouldReceive('getComponent')->andReturn('Row');

    $pageElement = new PageElement(elements: [$element1]);

    expect($pageElement->getElements())->toBe([$element1])
        ->and($pageElement->getNextButtonLabel())->toBeNull()
        ->and($pageElement->getPreviousButtonLabel())->toBeNull();
});

it('throws an exception when no elements are provided', function () {
    $this->expectException(ForestException::class);
    $this->expectExceptionMessage("Using 'elements' in a 'Page' configuration is mandatory");

    new PageElement(elements: []);
});

it('throws an exception when a Page element is used within elements', function () {
    $element = mock(PageElement::class)->makePartial();
    $element->shouldReceive('getComponent')->andReturn('Page');

    $this->expectException(ForestException::class);
    $this->expectExceptionMessage("'Page' component cannot be used within 'elements'");

    new PageElement(elements: [$element]);
});

it('can handle next and previous button labels', function () {
    $element = mock(LayoutElement::class)->makePartial();
    $element->shouldReceive('getComponent')->andReturn('Row');

    $pageElement = new PageElement(
        elements: [$element],
        nextButtonLabel: 'Next',
        previousButtonLabel: 'Previous'
    );

    expect($pageElement->getNextButtonLabel())->toBe('Next')
        ->and($pageElement->getPreviousButtonLabel())->toBe('Previous');
});
