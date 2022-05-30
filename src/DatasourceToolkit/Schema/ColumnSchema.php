<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class ColumnSchema
{
    /**
     * @param PrimitiveType|PrimitiveType[] $columnType
     * @param string|null                   $defaultValue
     * @param array|null                    $enumValues
     * @param bool                          $isPrimaryKey
     * @param bool                          $isReadOnly
     * @param bool                          $isSortable
     * @param string                        $type
     * @param array                         $validation
     */
    public function __construct(
        protected array|PrimitiveType $columnType,
        protected bool $isPrimaryKey = false,
        protected bool $isReadOnly = false,
        protected bool $isSortable = false,
        protected string $type = 'Column',
        protected ?string $defaultValue = null,
        protected array $enumValues = [],
        protected array $validation = [],
    ) {
    }
}
