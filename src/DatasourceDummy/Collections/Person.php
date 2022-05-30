<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class Person extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'firstName' => new ColumnSchema(
                columnType: PrimitiveType::String(),
            ),
            'lastName' => new ColumnSchema(
                columnType: PrimitiveType::String(),
            ),
        ];

        parent::__construct($dataSource, 'Library', $fields);
        $this->dataSource = $dataSource;
    }
}
