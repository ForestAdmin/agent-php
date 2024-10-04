<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

abstract class BaseLayoutElement
{
    protected string $type;

    protected string $label;

    protected ?string $description;

    public function __construct(string $type, string $label, ?string $description = null)
    {
        $this->type = $type;
        $this->label = $label;
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
