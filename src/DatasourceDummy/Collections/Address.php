<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;

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
