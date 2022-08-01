<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class ObjectiveChart extends Chart
{
    public function serialize(): array
    {
        return [
            'value'     => $this->data[0],
            'objective' => $this->data[1],
        ];
    }
}
