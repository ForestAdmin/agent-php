<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout;

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\ActionFieldFactory;

class RowElement extends BaseLayoutElement
{
    public function __construct(protected array $fields)
    {
        parent::__construct(component: 'Row');
        $this->fields = $this->instanciateSubfields($fields);
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    protected function instanciateSubfields(array $fields): array
    {
        return array_map(static function ($field) {
            return ActionFieldFactory::build($field); // to check
        }, $fields);
    }
}
