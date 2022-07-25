<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query;

class Sort
{
    public function __construct(private string $field, private bool $ascending = true)
    {
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return bool
     */
    public function isAscending(): bool
    {
        return $this->ascending;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->isAscending() ? 'ASC' : 'DESC';
    }
}
