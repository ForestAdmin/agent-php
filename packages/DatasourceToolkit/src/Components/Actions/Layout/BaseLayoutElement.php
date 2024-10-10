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
        foreach ($this->keys() as $attribute) {
            if ($attribute === 'type') {
                continue;
            }
            $result[$attribute] = $this->$attribute;
        }

        return $result;
    }

    public function keys(): array
    {
        return array_keys(get_object_vars($this));
    }
}
