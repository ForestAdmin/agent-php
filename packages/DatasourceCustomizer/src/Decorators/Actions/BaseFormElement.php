<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

class BaseFormElement
{
    public function __construct(
        protected string $type,
    ) {
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

    public function getType(): string
    {
        return $this->type;
    }

    public function isStatic(): bool
    {
        foreach ($this->keys() as $attribute) {
            if (is_callable($this->$attribute)) {
                return false;
            }
        }

        return true;
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->keys() as $attribute) {
            $result[$attribute] = $this->$attribute;
        }

        return $result;
    }

    public function keys(): array
    {
        return array_keys(get_object_vars($this));
    }
}
