<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\ActionScope;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

class Book extends BaseCollection
{
    protected $records = [
        [
            'id'          => 1,
            'title'       => 'title1',
            'publication' => '2022-01-01',
            'author'      => 'dsfqdsf',
        ],
        [
            'id'          => 2,
            'title'       => 'title2',
            'publication' => '2022-01-02',
            'author'      => 'fdsdf',
        ],
        [
            'id'          => 3,
            'title'       => 'title3',
            'publication' => '2022-01-03',
            'author'      => 'qsdfsdf',
        ],
    ];

    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id'          => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'title'       => new ColumnSchema(
                columnType: PrimitiveType::STRING,
                defaultValue: 'Foo'
            ),
            'publication' => new ColumnSchema(
                columnType: PrimitiveType::DATE,
            ),
            'author'      => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
        ];
        parent::__construct($dataSource, 'Book', $fields);

        $this->dataSource = $dataSource;
    }

    public function makeTransformer()
    {
        return new BasicArrayTransformer();
    }
}
