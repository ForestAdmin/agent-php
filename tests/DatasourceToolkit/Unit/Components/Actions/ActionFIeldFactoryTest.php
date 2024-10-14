<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\ActionFieldFactory;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\HtmlBlockElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\PageElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\RowElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement;

it('builds an ActionField from a DynamicField', function () {
    $dynamicField = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('text')
        ->shouldReceive('getLabel')->andReturn('Field Label')
        ->shouldReceive('getId')->andReturn('Field id')
        ->shouldReceive('getDescription')->andReturn('Field Description')
        ->shouldReceive('isRequired')->andReturn(true)
        ->shouldReceive('isReadOnly')->andReturn(false)
        ->shouldReceive('getValue')->andReturn('Value')
        ->shouldReceive('getEnumValues')->andReturn(['Option1', 'Option2'])
        ->shouldReceive('getCollectionName')->andReturn('Collection')
        ->getMock();

    $actionField = ActionFieldFactory::buildField($dynamicField);

    expect($actionField->getType())->toBe('text')
        ->and($actionField->getLabel())->toBe('Field Label')
        ->and($actionField->getId())->toBe('Field id')
        ->and($actionField->getDescription())->toBe('Field Description')
        ->and($actionField->isRequired())->toBeTrue()
        ->and($actionField->isReadOnly())->toBeFalse()
        ->and($actionField->getValue())->toBe('Value')
        ->and($actionField->getEnumValues())->toBe(['Option1', 'Option2'])
        ->and($actionField->getCollectionName())->toBe('Collection');
});

it('builds a SeparatorElement for a layout component of type Separator', function () {
    $element = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('Separator')
        ->getMock();

    $separatorElement = ActionFieldFactory::build($element);

    expect($separatorElement)->toBeInstanceOf(SeparatorElement::class);
});

it('builds a HtmlBlockElement for a layout component of type HtmlBlock', function () {
    $element = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('HtmlBlock')
        ->shouldReceive('getContent')->andReturn('<p>foo</p>')
        ->getMock();

    $htmlBlockElement = ActionFieldFactory::build($element);

    expect($htmlBlockElement)->toBeInstanceOf(HtmlBlockElement::class)
        ->and($htmlBlockElement->getContent())->toEqual('<p>foo</p>');
});


it('builds an InputElement for a layout component of type Input', function () {
    $element = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('Input')
        ->shouldReceive('getFieldId')->andReturn('input_1')
        ->getMock();

    $inputElement = ActionFieldFactory::build($element);

    expect($inputElement)->toBeInstanceOf(InputElement::class)
        ->and($inputElement->getFieldId())->toBe('input_1');
});

it('returns null for a RowElement with no fields', function () {
    $element = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('Row')
        ->shouldReceive('getFields')->andReturn([])
        ->getMock();

    $rowElement = ActionFieldFactory::build($element);

    expect($rowElement)->toBeNull();
});

it('builds a RowElement with fields', function () {
    $field1 = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('text')
        ->shouldReceive('getLabel')->andReturn('Label 1')
        ->shouldReceive('getId')->andReturn('id 1')
        ->shouldReceive('getDescription')->andReturn('Description 1')
        ->shouldReceive('isRequired')->andReturn(true)
        ->shouldReceive('isReadOnly')->andReturn(false)
        ->shouldReceive('getValue')->andReturn('Value 1')
        ->shouldReceive('getEnumValues')->andReturn([])
        ->shouldReceive('getCollectionName')->andReturn('Collection 1')
        ->getMock();

    $field2 = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('text')
        ->shouldReceive('getLabel')->andReturn('Label 2')
        ->shouldReceive('getId')->andReturn('id 2')
        ->shouldReceive('getDescription')->andReturn('Description 2')
        ->shouldReceive('isRequired')->andReturn(true)
        ->shouldReceive('isReadOnly')->andReturn(false)
        ->shouldReceive('getValue')->andReturn('Value 2')
        ->shouldReceive('getEnumValues')->andReturn([])
        ->shouldReceive('getCollectionName')->andReturn('Collection 2')
        ->getMock();

    $element = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('Row')
        ->shouldReceive('getFields')->andReturn([$field1, $field2])
        ->getMock();

    $rowElement = ActionFieldFactory::build($element);

    expect($rowElement)->toBeInstanceOf(RowElement::class)
        ->and(count($rowElement->getFields()))->toBe(2);
});

it('builds a PageElement with elements, nextButtonLabel, and previousButtonLabel', function () {
    $htmlBlockElement = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('HtmlBlock')
        ->shouldReceive('getContent')->andReturn('<h1>Charge the credit card of the customer</h1>')
        ->getMock();

    $field1 = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('text')
        ->shouldReceive('getLabel')->andReturn('Label 1')
        ->shouldReceive('getId')->andReturn('id 1')
        ->shouldReceive('getDescription')->andReturn('Description 1')
        ->shouldReceive('isRequired')->andReturn(true)
        ->shouldReceive('isReadOnly')->andReturn(false)
        ->shouldReceive('getValue')->andReturn('Value 1')
        ->shouldReceive('getEnumValues')->andReturn([])
        ->shouldReceive('getCollectionName')->andReturn('Collection 1')
        ->getMock();

    $field2 = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('text')
        ->shouldReceive('getLabel')->andReturn('Label 2')
        ->shouldReceive('getId')->andReturn('id 2')
        ->shouldReceive('getDescription')->andReturn('Description 2')
        ->shouldReceive('isRequired')->andReturn(true)
        ->shouldReceive('isReadOnly')->andReturn(false)
        ->shouldReceive('getValue')->andReturn('Value 2')
        ->shouldReceive('getEnumValues')->andReturn([])
        ->shouldReceive('getCollectionName')->andReturn('Collection 2')
        ->getMock();

    $rowElement = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('Row')
        ->shouldReceive('getFields')->andReturn([$field1, $field2])
        ->getMock();

    $element = \Mockery::mock(DynamicField::class)
        ->shouldReceive('getType')->andReturn('Layout')
        ->shouldReceive('getComponent')->andReturn('Page')
        ->shouldReceive('getElements')->andReturn([$htmlBlockElement, $rowElement])
        ->shouldReceive('getNextButtonLabel')->andReturn('my next button')
        ->shouldReceive('getPreviousButtonLabel')->andReturn('my previous button')
        ->getMock();

    $pageElement = ActionFieldFactory::build($element);

    expect($pageElement)->toBeInstanceOf(PageElement::class)
        ->and(count($pageElement->getElements()))->toBe(2)
        ->and($pageElement->getNextButtonLabel())->toBe('my next button')
        ->and($pageElement->getPreviousButtonLabel())->toBe('my previous button');
});

it('returns null if PageElement has no elements', function () {
    $mockElement = \Mockery::mock('Element')
        ->shouldReceive('getComponent')->andReturn('Page')
        ->shouldReceive('getElements')->andReturn([])
        ->getMock();

    $result = ActionFieldFactory::buildLayoutElement($mockElement);

    expect($result)->toBeNull();
});
