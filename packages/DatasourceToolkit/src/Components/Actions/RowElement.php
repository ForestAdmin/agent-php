<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions;

class RowElement extends BaseLayoutElement
{
    private array $fields;

    public function __construct(string $label, array $fields, ?string $description = null)
    {
        parent::__construct('Row', $label, $description);
        $this->fields = $fields;
    }

    public function getFields(): array
    {
        return $this->fields;
    }
}
