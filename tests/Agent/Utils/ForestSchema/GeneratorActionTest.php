<?php

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\GeneratorAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\BaseAction;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\DynamicField;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\HtmlBlockElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\PageElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\RowElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\FormLayoutElement\SeparatorElement;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\ActionScope;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Types\FieldType;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\ActionField;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\InputElement;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement as ActionSeparatorElement;
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
                'id'                => 'Book-0-send-email',
                'name'              => 'Send email',
                'type'              => 'single',
                'baseUrl'           => null,
                'endpoint'          => '/forest/_actions/Book/0/send-email',
                'httpMethod'        => 'POST',
                'redirect'          => null,
                'download'          => false,
                'fields'            => [],
                'layout'            => [],
                'hooks'             => [ 'load' => false, 'change' => ['changeHook'] ],
                'submitButtonLabel' => null,
                'description'       => null,
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
                'layout'            => [['component' => 'input', 'fieldId' => 'label']],
                'hooks'             => [ 'load' => false, 'change' => ['changeHook'] ],
                'submitButtonLabel' => null,
                'description'       => null,
            ]
        );
    });
});

describe('with layout elements', function () {
    test('buildSchema() should generate schema correctly with separator element', function () {
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
                    ),
                    new SeparatorElement(),
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
                        'description'   => null,
                        'isRequired'    => false,
                        'isReadOnly'    => false,
                        'field'         => 'label',
                        'type'          => 'String',
                        'defaultValue'  => null,
                    ],
                ],
                'layout' => [
                    ['component' => 'input', 'fieldId' => 'label'],
                    ['component' => 'separator'],
                ],
                'hooks'             => [ 'load' => false, 'change' => ['changeHook'] ],
                'submitButtonLabel' => null,
                'description'       => null,
            ]
        );
    });

    test('buildSchema() should generate schema correctly with html block element', function () {
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
                    ),
                    new HtmlBlockElement('<p>test</p>'),
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
                        'description'   => null,
                        'isRequired'    => false,
                        'isReadOnly'    => false,
                        'field'         => 'label',
                        'type'          => 'String',
                        'defaultValue'  => null,
                    ],
                ],
                'layout' => [
                    ['component' => 'input', 'fieldId' => 'label'],
                    ['component' => 'htmlBlock', 'content' => '<p>test</p>'],
                ],
                'hooks'             => [ 'load' => false, 'change' => ['changeHook'] ],
                'submitButtonLabel' => null,
                'description'       => null,
            ]
        );
    });

    test('buildSchema() should generate schema correctly with row element', function () {
        $actionDatasource = new DatasourceDecorator($this->bucket['datasource'], ActionCollection::class);
        $actionDatasource->getCollection('Book')->addAction(
            'Send email',
            new BaseAction(
                scope: ActionScope::SINGLE,
                execute: fn () => true,
                form: [
                    new RowElement(
                        fields: [
                            new DynamicField(
                                type: FieldType::STRING,
                                label: 'sub field 1',
                            ),
                            new DynamicField(
                                type: FieldType::STRING,
                                label: 'sub field 2',
                            ),
                        ]
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
                'id'                => 'Book-0-send-email',
                'name'              => 'Send email',
                'type'              => 'single',
                'baseUrl'           => null,
                'endpoint'          => '/forest/_actions/Book/0/send-email',
                'httpMethod'        => 'POST',
                'redirect'          => null,
                'download'          => false,
                'submitButtonLabel' => null,
                'description'       => null,
                'fields'            => [
                    [
                        'description'   => null,
                        'isRequired'    => false,
                        'isReadOnly'    => false,
                        'field'         => 'sub field 1',
                        'type'          => 'String',
                        'defaultValue'  => null,
                    ],
                    [
                        'description'   => null,
                        'isRequired'    => false,
                        'isReadOnly'    => false,
                        'field'         => 'sub field 2',
                        'type'          => 'String',
                        'defaultValue'  => null,
                    ],
                ],
                'layout' => [
                    [
                        'component' => 'row',
                        'fields'    => [
                            ['component' => 'input', 'fieldId' => 'sub field 1'],
                            ['component' => 'input', 'fieldId' => 'sub field 2'],
                        ],
                    ],
                ],
                'hooks'      => [ 'load' => false, 'change' => ['changeHook'] ],
            ]
        );
    });

    test('buildSchema() should generate schema correctly with page element', function () {
        $actionDatasource = new DatasourceDecorator($this->bucket['datasource'], ActionCollection::class);
        $actionDatasource->getCollection('Book')->addAction(
            'Send email',
            new BaseAction(
                scope: ActionScope::SINGLE,
                execute: fn () => true,
                form: [
                    new PageElement(
                        elements: [
                            new HtmlBlockElement('<h1>Charge the credit card of the customer</h1>'),
                            new RowElement(
                                fields: [
                                    new DynamicField(
                                        type: FieldType::STRING,
                                        label: 'sub field 1',
                                    ),
                                    new DynamicField(
                                        type: FieldType::STRING,
                                        label: 'sub field 2',
                                    ),
                                ]
                            ),
                        ],
                        nextButtonLabel: 'my next button',
                        previousButtonLabel: 'my previous button',
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
                'id'                => 'Book-0-send-email',
                'name'              => 'Send email',
                'type'              => 'single',
                'baseUrl'           => null,
                'endpoint'          => '/forest/_actions/Book/0/send-email',
                'httpMethod'        => 'POST',
                'redirect'          => null,
                'download'          => false,
                'submitButtonLabel' => null,
                'description'       => null,
                'fields'            => [
                    [
                        'description'   => null,
                        'isRequired'    => false,
                        'isReadOnly'    => false,
                        'field'         => 'sub field 1',
                        'type'          => 'String',
                        'defaultValue'  => null,
                    ],
                    [
                        'description'   => null,
                        'isRequired'    => false,
                        'isReadOnly'    => false,
                        'field'         => 'sub field 2',
                        'type'          => 'String',
                        'defaultValue'  => null,
                    ],
                ],
                'layout' => [
                    [
                        'component' => 'page',
                        'elements'  => [
                            ['component' => 'htmlBlock', 'content' => '<h1>Charge the credit card of the customer</h1>'],
                            [
                                'component' => 'row',
                                'fields'    => [
                                    ['component' => 'input', 'fieldId' => 'sub field 1'],
                                    ['component' => 'input', 'fieldId' => 'sub field 2'],
                                ],
                            ],
                        ],
                        'nextButtonLabel'     => 'my next button',
                        'previousButtonLabel' => 'my previous button',
                    ],
                ],
                'hooks'      => [ 'load' => false, 'change' => ['changeHook'] ],
            ]
        );
    });

    test('extractFieldsAndLayout() should extract fields and layout from form', function () {
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
                    ),
                    new SeparatorElement(),
                ]
            )
        );

        $extract = GeneratorAction::extractFieldsAndLayout(
            $actionDatasource->getCollection('Book')->getForm(null, 'Send email')
        );

        expect($extract)->toEqual(
            [
                'fields'     => [new ActionField(type: 'String', label: 'label')],
                'layout'     => [
                    new InputElement(fieldId: 'label'),
                    new ActionSeparatorElement(),
                ],
            ]
        );
    });
});
