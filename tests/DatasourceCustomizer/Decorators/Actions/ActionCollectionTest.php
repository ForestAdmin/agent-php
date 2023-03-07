<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

function factoryActionCollection()
{
    $datasource = new Datasource();
    $collection = new Collection($datasource, 'Product');

    $datasource->addCollection($collection);
    buildAgent($datasource);

    $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);
    $datasourceDecorator->build();

    $baseAction = new BaseAction(
        scope: ActionScope::SINGLE,
        execute: fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO !!!!'),
        form: [
            new DynamicField(type: FieldType::NUMBER, label: 'amount'),
        ],
    );

    return [$datasourceDecorator, $baseAction];
}

test('addAction() should add action to actions list', function () {
    [$datasourceDecorator, $baseAction] = factoryActionCollection();

    $collection = $datasourceDecorator->getCollection('Product');
    $collection->addAction('action-test', $baseAction);

    expect($collection->getActions()->toArray())->toHaveKey('action-test');
});

test('execute() should return a success response', function (Caller $caller) {
    [$datasourceDecorator, $baseAction] = factoryActionCollection();

    $collection = $datasourceDecorator->getCollection('Product');
    $collection->addAction('action-test', $baseAction);

    expect($collection->execute($caller, 'action-test', []))->toEqual(
        [
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
    $datasourceDecorator = factoryActionCollection()[0];
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
    $datasourceDecorator = factoryActionCollection()[0];
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
            'is_action' => true,
            'type'      => 'Error',
            'html'      => '<div><p class="c-clr-1-4 l-mb">you failed</p></div>',
            'status'    => 400,
            'error'     => 'error',
        ],
    );
})->with('caller');

test('execute() should throw an error when the action doesn\'t exist', function (Caller $caller) {
    $datasourceDecorator = factoryActionCollection()[0];

    $collection = $datasourceDecorator->getCollection('Product');

    expect(fn () => $collection->execute($caller, 'action-test', []))->toThrow(ForestException::class, '🌳🌳🌳 Action action-test is not implemented');
})->with('caller');
