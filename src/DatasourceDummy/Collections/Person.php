<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToOneSchema;

class Person extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'id' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'firstName' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'lastName' => new ColumnSchema(
                columnType: PrimitiveType::STRING,
            ),
            'addressId' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
            ),
            'books' => new OneToManySchema(
                originKey: 'authorId',
                originKeyTarget: 'id',
                foreignCollection: 'Book',
                inverseRelationName: 'Person',
            ),
            'address' => new OneToOneSchema(
                originKey: 'originKeyId',
                originKeyTarget: 'id',
                foreignCollection: 'Address',
                inverseRelationName: 'Person',
            ),
        ];

        parent::__construct($dataSource, 'Person', $fields);
        $this->dataSource = $dataSource;
    }
}
