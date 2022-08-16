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
        return [
            'value'     => $this->value,
            'objective' => $this->objective,
        ];
    }
}
