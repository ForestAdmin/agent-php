<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class Address extends DummyCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id'          => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'street'      => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'city'        => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'postal_code' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
        ];
        parent::__construct($dataSource, 'Address', $fields);
        $this->dataSource = $dataSource;
    }
}
