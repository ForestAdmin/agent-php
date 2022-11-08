<?php

namespace ForestAdmin\AgentPHP\DatasourceDummy\Collections;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema\Concerns\PrimitiveType;

class LibraryBook extends BaseCollection
{
    public function __construct(DatasourceContract $dataSource)
    {
        $fields = [
            'bookId' => new ColumnSchema(
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
