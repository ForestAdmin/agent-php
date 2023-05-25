<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

class ColumnSchema
{
    /**
     * @param array|string $columnType
     * @param array        $filterOperators
     * @param bool         $isPrimaryKey
     * @param bool         $isReadOnly
     * @param bool         $isSortable
     * @param string       $type
     * @param string|null  $defaultValue
     * @param array        $enumValues
     * @param array        $validation
     */
    public function __construct(
        protected array|string $columnType,
        protected array $filterOperators = [],
        protected bool $isPrimaryKey = false,
        protected bool $isReadOnly = false,
        protected bool $isSortable = true,
        protected string $type = 'Column',
        protected ?string $defaultValue = null,
        protected array $enumValues = [],
        protected array $validation = [],
    ) {
    }

    public function getColumnType(): array|string
    {
        return $this->columnType;
    }

    public function getFilterOperators(): array
    {
        return $this->filterOperators;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    public function setIsReadOnly(bool $isReadOnly): void
    {
        $this->isReadOnly = $isReadOnly;
    }

    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    public function setSortable(bool $sortable): self
    {
        $this->isSortable = $sortable;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function getEnumValues(): array
    {
        return $this->enumValues;
    }

    public function getValidation(): array
    {
        return $this->validation;
    }

    public function setValidation(array $validation): void
    {
        $this->validation = $validation;
    }

    public function setFilterOperators(array $filterOperators): void
    {
        $this->filterOperators = $filterOperators;
    }

    public function setColumnType(array|string $columnType): void
    {
        $this->columnType = $columnType;
    }
}
