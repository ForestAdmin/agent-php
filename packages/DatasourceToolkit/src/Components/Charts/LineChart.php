<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

use ForestAdmin\AgentPHP\DatasourceToolkit\Validations\ChartValidator;

class LineChart extends Chart
{
    public function __construct(protected array $data)
    {
    }

    public function serialize()
    {
        foreach ($this->data as $item) {
            ChartValidator::validate((! array_key_exists('label', $item) || ! array_key_exists('values', $item)), $item, "'label', 'values'");
        }

        return $this->data;
    }
}
