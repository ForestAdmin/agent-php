<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ChartValidator;

class PieChart extends Chart
{
    public function __construct(protected array $data)
    {
    }

    public function serialize()
    {
        foreach ($this->data as $item) {
            ChartValidator::validate((! array_key_exists('key', $item) || ! array_key_exists('value', $item)), $item, "'key', 'value'");
        }

        return $this->data;
    }
}
