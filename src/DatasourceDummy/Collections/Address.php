<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class Address extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id'          => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'street'      => new ColumnSchema(
                columnType: PrimitiveType::String(),
            ),
            'city'        => new ColumnSchema(
                columnType: PrimitiveType::String(),
            ),
            'postal_code' => new ColumnSchema(
                columnType: PrimitiveType::String(),
            ),
        ];
        parent::__construct($dataSource, 'Address', $fields);
        $this->dataSource = $dataSource;
    }
}
