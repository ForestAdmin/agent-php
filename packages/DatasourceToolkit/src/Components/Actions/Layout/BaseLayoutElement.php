<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

class BaseLayoutElement
{
    protected string $type;

    /**
     * @param string $component
     */
    public function __construct(
        protected string $component,
    ) {
        $this->type = 'Layout';
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    public function setComponent(string $component): void
    {
        $this->component = $component;
    }

    public function toArray(): array
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($key !== 'type') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function __set($key, $value)
    {
        $this->$key = $value;
    }

    public function __get($key)
    {
        return $this->$key ?? null;
    }

    public function __isset($key)
    {
        return isset($this->$key);
    }
}
