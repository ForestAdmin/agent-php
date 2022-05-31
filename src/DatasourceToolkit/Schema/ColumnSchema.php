<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;

class ColumnSchema
{
    /**
     * @param PrimitiveType|PrimitiveType[] $columnType
     * @param bool                          $isPrimaryKey
     * @param bool                          $isReadOnly
     * @param bool                          $isSortable
     * @param string                        $type
     * @param string|null                   $defaultValue
     * @param array                         $enumValues
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

    /**
     * @return array|PrimitiveType|PrimitiveType[]
     */
    public function getColumnType(): array|PrimitiveType
    {
        return $this->columnType;
    }

    /**
     * @return bool
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * @return bool
     */
    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @return array
     */
    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    /**
     * @return array
     */
    public function getValidation(): array
    {
        return $this->validation;
    }

}
