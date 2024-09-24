<?php

use ForestAdmin\AgentPHP\Agent\Serializer\JsonApiSerializer;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Relations\ManyToOneSchema;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

$before = static function ($testCase) {
    $datasource = new Datasource();
    $collectionUser = new Collection($datasource, 'Book');
    $collectionUser->addFields(
        [
            'id'             => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'          => new ColumnSchema(columnType: PrimitiveType::STRING),
            'category_value' => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'category'       => new ManyToOneSchema(
                foreignKey: 'category_value',
                foreignKeyTarget: 'value',
                foreignCollection: 'Category',
            ),
        ]
    );

    $collectionCategory = new Collection($datasource, 'Category');
    $collectionCategory->addFields(
        [
            'value'         => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'label'         => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    $datasource->addCollection($collectionUser);
    $datasource->addCollection($collectionCategory);
    $testCase->buildAgent($datasource);
};

test('setName() should set the name value', function () {
    $transformer = new BaseTransformer('foo');
    $transformer->setName('bar');

    expect($this->invokeProperty($transformer, 'name'))->toEqual('bar');
});


test('transform() should serialize the data and set default includes', function () use ($before) {
    $before($this);
    $transformer = new BaseTransformer('Book');
    $serialized = $transformer->transform([
        'id'             => 1,
        'title'          => 'foo',
        'category_value' => 1,
        'category'       => [
            'value' => 1,
            'label' => 'cat1',
        ],
    ]);

    expect($serialized)->toEqual(['id' => 1, 'title' => 'foo', 'category_value' => 1])
        ->and($transformer->getDefaultIncludes())->toEqual(['category']);
});

test('transformer should serialize data with included relation', function () use ($before) {
    $before($this);
    $transformer = new BaseTransformer('Book');
    $fractal = new Manager();
    $fractal->setSerializer(new JsonApiSerializer());
    $data = [
        'id'             => 1,
        'title'          => 'foo',
        'category_value' => 1,
        'category'       => [
            'value' => 1,
            'label' => 'cat1',
        ],
    ];
    $fractal->createData(new Item($data, $transformer, 'Book'))->toArray();
    $transformer->transform($data);
    $serialized = $transformer->processIncludedResources($transformer->getCurrentScope(), $data);

    expect($serialized)->toEqual(
        [
            'category' => [
                'data' => [
                    'type'       => 'Category',
                    'id'         => '1',
                    'attributes' => [
                        'value' => 1,
                        'label' => 'cat1',
                    ],
                ],
            ],
        ]
    );
});
