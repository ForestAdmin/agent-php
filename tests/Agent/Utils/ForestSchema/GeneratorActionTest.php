<?php

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

beforeEach(function () {
    $datasource = new Datasource();

    $collectionBook = new Collection($datasource, 'Book');
    $collectionBook->addFields(['id' => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true)]);
    $datasource->addCollection($collectionBook);
    $this->buildAgent($datasource);

    $this->bucket['datasource'] = $datasource;
});

describe('without form', function () {
    test('buildSchema() should generate schema correctly', function () {
        $actionDatasource = new DatasourceDecorator($this->bucket['datasource'], ActionCollection::class);
        $actionDatasource->getCollection('Book')->addAction('Send email', new BaseAction(ActionScope::SINGLE, fn () => true));

        $schema = GeneratorAction::buildSchema(
            $actionDatasource->getCollection('Book'),
            'Send email'
        );

        expect($schema)->toEqual(
            [
                'id'         => 'Book-0-send-email',
                'name'       => 'Send email',
                'type'       => 'single',
                'baseUrl'    => null,
                'endpoint'   => '/forest/_actions/Book/0/send-email',
                'httpMethod' => 'POST',
                'redirect'   => null,
                'download'   => false,
                'fields'     => [],
                'hooks'      => [ 'load' => false, 'change' => ['changeHook'] ],
            ]
        );
    });
});

describe('with no hooks', function () {
    test('buildSchema() should generate schema correctly', function () {
        $actionDatasource = new DatasourceDecorator($this->bucket['datasource'], ActionCollection::class);
        $actionDatasource->getCollection('Book')->addAction(
            'Send email',
            new BaseAction(
                scope: ActionScope::SINGLE,
                execute: fn () => true,
                form: [
                    new DynamicField(
                        type: FieldType::STRING,
                        label: 'label',
                        description: 'email',
                        isRequired: true,
                        value: ''
                    ),
                ]
            )
        );

        $schema = GeneratorAction::buildSchema(
            $actionDatasource->getCollection('Book'),
            'Send email'
        );

        expect($schema)->toEqual(
            [
                'id'         => 'Book-0-send-email',
                'name'       => 'Send email',
                'type'       => 'single',
                'baseUrl'    => null,
                'endpoint'   => '/forest/_actions/Book/0/send-email',
                'httpMethod' => 'POST',
                'redirect'   => null,
                'download'   => false,
                'fields'     => [
                    [
                        'description'   => 'email',
                        'isRequired'    => true,
                        'isReadOnly'    => false,
                        'field'         => 'label',
                        'type'          => 'String',
                        'defaultValue'  => '',
                    ],
                ],
                'hooks'      => [ 'load' => false, 'change' => ['changeHook'] ],
            ]
        );
    });
});
