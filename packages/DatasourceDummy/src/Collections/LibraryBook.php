<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class LibraryBook extends DummyCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'bookId'    => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
            'libraryId' => new ColumnSchema(
                columnType: PrimitiveType::NUMBER,
                isPrimaryKey: true
            ),
        ];

        parent::__construct($dataSource, 'LibraryBook', $fields);
        $this->dataSource = $dataSource;
    }
};
