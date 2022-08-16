<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ChartValidator;

class ObjectiveChart extends Chart
{
    public function __construct(protected int $value, protected int $objective)
    {
    }

    public function serialize()
    {
        ChartValidator::validate((! array_key_exists('objective', $this->data) || ! array_key_exists('value', $this->data)), $this->data, "'objective', 'value'");

        return [
            'value'     => $this->value,
            'objective' => $this->objective,
        ];
    }
}
