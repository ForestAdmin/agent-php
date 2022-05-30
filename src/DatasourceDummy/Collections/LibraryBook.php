<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class LibraryBook extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'bookId' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
            'libraryId' => new ColumnSchema(
                columnType: PrimitiveType::Number(),
                isPrimaryKey: true
            ),
        ];

        parent::__construct($dataSource, 'Library', $fields);
        $this->dataSource = $dataSource;
    }
};
