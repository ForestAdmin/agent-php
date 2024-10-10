<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

class RowElement extends BaseLayoutElement
{
    public function __construct(protected array $fields)
    {
        parent::__construct(component: 'Row');
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function __isset($key)
    {
        return isset($this->$key);
    }
}
