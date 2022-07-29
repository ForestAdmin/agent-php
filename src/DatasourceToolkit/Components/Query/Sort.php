<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query;

class Sort
{
    private array $fields;

    public function __construct(array $items)
    {
        foreach ($items as $item) {
            if ($this->fieldIsAscending($item)) {
                $field = ['field' => $item, 'order' => 'ASC'];
            } else {
                $field = ['field' => substr($item, 1), 'order' => 'DESC'];
            }
            $this->fields[] = $field;
        }
    }

    /**
     * @param $value
     * @return bool
     */
    public function fieldIsAscending($value): bool
    {
        if ($value[0] === '-') {
            return false;
        }

        return true;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function nest(string $prefix = ''): self
    {
        foreach ($this->fields as &$field) {
            $field['field'] = $prefix . ':' . $field['field'];
        }

        return $this;
    }
}
