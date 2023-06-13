<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\ConditionTree\Operators;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class Book extends DummyCollection
{
    protected $records = [
        [
            'id'          => 1,
            'title'       => 'title1',
            'publication' => '2022-01-01',
            'author'      => 'John',
            'user_id'     => 1,
        ],
        [
            'id'          => 2,
            'title'       => 'title2',
            'publication' => '2022-01-02',
            'author'      => 'Sarah',
            'user_id'     => 1,
        ],
        [
            'id'          => 3,
            'title'       => 'title3',
            'publication' => '2022-01-03',
            'author'      => 'Baudry',
            'user_id'     => 1,
        ],
    ];

    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id'           => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: Operators::getAllOperators(),
                isPrimaryKey: true
            ),
            'title'        => new ColumnSchema(
                columnType: PrimitiveType::STRING,
                defaultValue: 'Foo'
            ),
            'publication'  => new ColumnSchema(
                columnType: PrimitiveType::DATE,
            ),
            'author'       => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'user_id'      => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                filterOperators: Operators::getAllOperators(),
            ),
        ];
        parent::__construct($dataSource, 'Book', $fields);

        $this->dataSource = $dataSource;
    }

    public function makeTransformer()
    {
        return new BaseTransformer($this->name);
    }
}
