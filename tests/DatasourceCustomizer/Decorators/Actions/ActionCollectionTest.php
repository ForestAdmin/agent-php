<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

describe('getInverseRelation() when inverse relations is missing', function () {
    beforeEach(function () {
        $datasource = new Datasource();
        $collection = new Collection($datasource, 'Product');

        $datasource->addCollection($collection);
        $this->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
        $datasourceDecorator->build();

        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO !!!!'),
            form: [
                new DynamicField(type: FieldType::NUMBER, label: 'amount'),
            ],
        );

        $this->bucket = [$datasourceDecorator, $baseAction];
    });

    test('addAction() should add action to actions list', function () {
        [$datasourceDecorator, $baseAction] = $this->bucket;

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getActions()->toArray())->toHaveKey('action-test');
    });

    test('execute() should return a success response', function (Caller $caller) {
        [$datasourceDecorator, $baseAction] = $this->bucket;

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->execute($caller, 'action-test', []))->toEqual(
            [
                'headers'   => [],
                'is_action' => true,
                'type'      => 'Success',
                'success'   => 'BRAVO !!!!',
                'refresh'   => [
                    'relationships' => [],
                ],
                'html'      => null,
            ],
        );
    })->with('caller');

    test('execute() should return a default response when execute response it not a response builder', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];
        $baseAction = new BaseAction(
            scope: ActionScope::SCOPE,
            execute: fn ($context, $responseBuilder) => ['ok'],
            form: [
                new DynamicField(type: FieldType::NUMBER, label: 'amount'),
            ],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->execute($caller, 'action-test', []))->toEqual(
            [
                'ok',
            ],
        );
    })->with('caller');

    test('execute() should return the corresponding response builder', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];
        $baseAction = new BaseAction(
            'SINGLE',
            fn ($context, $responseBuilder) => $responseBuilder->error(
                'error',
                ['html' => '<div><p class="c-clr-1-4 l-mb">you failed</p></div>']
            )
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->execute($caller, 'action-test', []))->toEqual(
            [
                'headers'   => [],
                'is_action' => true,
                'type'      => 'Error',
                'html'      => '<div><p class="c-clr-1-4 l-mb">you failed</p></div>',
                'status'    => 400,
                'error'     => 'error',
            ],
        );
    })->with('caller');

    test('execute() should throw an error when the action doesn\'t exist', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];

        $collection = $datasourceDecorator->getCollection('Product');

        expect(fn () => $collection->execute($caller, 'action-test', []))->toThrow(ForestException::class, '🌳🌳🌳 Action action-test is not implemented');
    })->with('caller');

    test('getForm() should return an empty array when the action doesn\'t exist', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];
        $collection = $datasourceDecorator->getCollection('Product');

        expect($collection->getForm($caller, 'action-test', ['label' => 'Foo'], new Filter()))->toEqual([]);
    })->with('caller');

    test('getForm() should return an empty array when the action has a empty form', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];
        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO !!!!'),
            form: [],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['label' => 'Foo'], new Filter()))->toEqual([]);
    })->with('caller');

    test('getForm() should return return an array of ActionField', function (Caller $caller) {
        [$datasourceDecorator, $baseAction] = $this->bucket;

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['label' => 'Foo'], new Filter()))->toEqual([
            new ActionField(
                type: 'Number',
                label: 'amount',
            ),
        ]);
    })->with('caller');

    test('getForm() should work with changeField param', function (Caller $caller) {
        [$datasourceDecorator, $baseAction] = $this->bucket;

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['label' => 'Foo'], new Filter(), 'myChangeField'))->toEqual([
            new ActionField(
                type: 'Number',
                label: 'amount',
            ),
        ]);
    })->with('caller');

    test('getForm() should compute dynamic default value no data on load hook', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];

        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->error('meeh'),
            form: [
                new DynamicField(type: FieldType::STRING, label: 'firstname', defaultValue: fn () => 'DynamicDefault'),
                new DynamicField(type: FieldType::STRING, label: 'lastname', isReadOnly: fn ($context) => $context->getFormValue('firstname') !== null),
            ],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', [], new Filter()))->toEqual([
            new ActionField(
                type: 'String',
                label: 'firstname',
                watchChanges: true,
                value: 'DynamicDefault'
            ),
            new ActionField(
                type: 'String',
                label: 'lastname',
                watchChanges: false
            ),
        ]);
    })->with('caller');

    test('getForm() should compute dynamic default value on added field', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];

        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->error('meeh'),
            form: [
                new DynamicField(type: FieldType::STRING, label: 'firstname', defaultValue: fn () => 'DynamicDefault'),
                new DynamicField(type: FieldType::STRING, label: 'lastname', isReadOnly: fn ($context) => $context->getFormValue('firstname') !== null),
            ],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['lastname' => 'value'], new Filter()))->toEqual([
            new ActionField(
                type: 'String',
                label: 'firstname',
                watchChanges: true,
                value: 'DynamicDefault'
            ),
            new ActionField(
                type: 'String',
                label: 'lastname',
                watchChanges: false,
                value: 'value'
            ),
        ]);
    })->with('caller');

    test('getForm() should compute readonly (false) and keep null firstname', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];

        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->error('meeh'),
            form: [
                new DynamicField(type: FieldType::STRING, label: 'firstname', defaultValue: fn () => 'DynamicDefault'),
                new DynamicField(type: FieldType::STRING, label: 'lastname', isReadOnly: fn ($context) => $context->getFormValue('firstname') !== null),
            ],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['firstname' => null], new Filter()))->toEqual([
            new ActionField(
                type: 'String',
                label: 'firstname',
                watchChanges: true,
                value: null
            ),
            new ActionField(
                type: 'String',
                label: 'lastname',
                watchChanges: false
            ),
        ]);
    })->with('caller');

    test('getForm() should compute readonly (true) and keep "John" firstname', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];

        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->error('meeh'),
            form: [
                new DynamicField(type: FieldType::STRING, label: 'firstname', defaultValue: fn () => 'DynamicDefault'),
                new DynamicField(type: FieldType::STRING, label: 'lastname', isReadOnly: fn ($context) => $context->getFormValue('firstname') !== null),
            ],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['firstname' => 'John'], new Filter()))->toEqual([
            new ActionField(
                type: 'String',
                label: 'firstname',
                watchChanges: true,
                value: 'John',
            ),
            new ActionField(
                type: 'String',
                label: 'lastname',
                watchChanges: false,
                isReadOnly: true
            ),
        ]);
    })->with('caller');

    test('getForm() should be able to compute form with a if condition', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];

        $baseAction = new BaseAction(
            scope: 'SINGLE',
            execute: fn ($context, $responseBuilder) => $responseBuilder->success('Thank you for your review!'),
            form: [
                new DynamicField(type: FieldType::ENUM, label: 'Rating', enumValues: [1,2,3,4,5]),
                new DynamicField(
                    type: FieldType::STRING,
                    label: 'Put a comment',
                    if: fn ($context) => $context->getFormValue('Rating') !== null && $context->getFormValue('Rating') < 4
                ),
            ],
        );

        $collection = $datasourceDecorator->getCollection('Product');
        $collection->addAction('action-test', $baseAction);

        expect($collection->getForm($caller, 'action-test', ['firstname' => 'John'], new Filter()))->toEqual([
            new ActionField(
                type: 'Enum',
                label: 'Rating',
                watchChanges: true,
                enumValues: [1,2,3,4,5]
            ),
        ]);
    })->with('caller');


    test('evaluate() should not evaluate a string that is a PHP function name', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];
        $collection = $datasourceDecorator->getCollection('Product');
        $context = new ActionContext($collection, $caller, new PaginatedFilter());

        expect($this->invokeMethod($collection, 'evaluate', [$context, 'date']))->toEqual('date');
    })->with('caller');

    test('evaluate() should evaluate the closure', function (Caller $caller) {
        $datasourceDecorator = $this->bucket[0];
        $collection = $datasourceDecorator->getCollection('Product');
        $context = new ActionContext($collection, $caller, new PaginatedFilter());

        expect($this->invokeMethod($collection, 'evaluate', [$context, fn () => 'result']))->toEqual('result');
    })->with('caller');
});
