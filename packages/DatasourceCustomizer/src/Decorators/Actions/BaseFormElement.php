<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions;

class BaseFormElement
{
    public function __construct(
        protected string $type,
        array $extraArguments = [] // workaround like kwargs in other languages
    ) {
        foreach ($extraArguments as $key => $value) {
            $this->$key = $value;
        }
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
