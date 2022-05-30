<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ActionSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\ActionScope;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class Book extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'title' => new ColumnSchema(
                columnType: PrimitiveType::String(),
                defaultValue: 'Foo'
            ),
            'publication' => new ColumnSchema(
                columnType: PrimitiveType::Date(),
            ),
            'authorId' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                defaultValue: 35
            ),
        ];

        $this->addAction('Mark as live', new ActionSchema(scope: ActionScope::single(), staticForm: true));

        parent::__construct($dataSource, 'Book', $fields);
        $this->dataSource = $dataSource;
    }
}
