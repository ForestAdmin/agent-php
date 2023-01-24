<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class Library extends DummyCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id'   => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'name' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
        ];

        parent::__construct($dataSource, 'Library', $fields);
        $this->dataSource = $dataSource;
    }
}
