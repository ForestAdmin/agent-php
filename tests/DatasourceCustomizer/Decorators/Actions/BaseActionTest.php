<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context\ActionContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ResultBuilder;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\PaginatedFilter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\Tests\TestCase;

describe('Base Action', function () {
    $before = static function (TestCase $testCase, $withGeneratedFile = false, $staticForm = false) {
        $datasource = new Datasource();
        $collection = new Collection($datasource, 'Product');

        $datasource->addCollection($collection);
        $testCase->buildAgent($datasource);

        $datasourceDecorator = new DatasourceDecorator($datasource, ActionCollection::class);

        $baseAction = new BaseAction(
            scope: ActionScope::SINGLE,
            execute: fn ($context, $responseBuilder) => $responseBuilder->success('BRAVO !!!!'),
            isGenerateFile: $withGeneratedFile,
            form: [
                new DynamicField(type: FieldType::NUMBER, label: 'amount'),
                new DynamicField(type: FieldType::STRING, label: 'description', isRequired: true),
                new DynamicField(
                    type: FieldType::STRING,
                    label: 'amount X10',
                    isReadOnly: true,
                    value: $staticForm ? 'ok' : function ($context) {
                        return $context->getFormValue('amount') * 10;
                    }
                ),
            ],
        );

        $testCase->bucket = [$datasourceDecorator, $datasource, $baseAction];
    };

    test('callExecute() should work', function (Caller $caller) use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        $context = new ActionContext(new ActionCollection($datasourceDecorator->getCollection('Product'), $datasource), $caller, new PaginatedFilter());

        $resultBuilder = new ResultBuilder();

        expect($baseAction->callExecute($context, $resultBuilder))->toEqual(
            [
                'headers'   => [],
                'is_action' => true,
                'type'      => 'Success',
                'success'   => 'BRAVO !!!!',
                'refresh'   => [
                    'relationships' => [],
                ],
                'html'      => null,
            ]
        );
    })->with('caller');

    test('getForm() should work', function () use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->getForm())->toEqual([
            new DynamicField(type: FieldType::NUMBER, label: 'amount'),
            new DynamicField(type: FieldType::STRING, label: 'description', isRequired: true),
            new DynamicField(
                type: FieldType::STRING,
                label: 'amount X10',
                isReadOnly: true,
                value: function ($context) {
                    return $context->getFormValue('amount') * 10;
                }
            ),
        ]);
    });

    test('getScope() should work', function () use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->getScope())->toEqual(ActionScope::SINGLE);
    });

    test('getDescription() should be null if not set', function () use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->getDescription())->toBeNull();
    });

    test('getSubmitButtonLabel() should be null if not set', function () use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->getSubmitButtonLabel())->toBeNull();
    });

    test('isGenerateFile() should return false when isGenerateFile is false', function () use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->isGenerateFile())->toBeFalse();
    });

    test('isGenerateFile() should return true when isGenerateFile is true', function () use ($before) {
        $before($this, true);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->isGenerateFile())->toBeTrue();
    });

    test('isStaticForm() should return false when the form is not static', function () use ($before) {
        $before($this);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->isStaticForm())->toBeFalse();
    });

    test('isStaticForm() should return true when the form is not static', function () use ($before) {
        $before($this, false, true);
        [$datasourceDecorator, $datasource, $baseAction] = $this->bucket;

        expect($baseAction->isStaticForm())->toBeTrue();
    })->with('caller');
});
