<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class ObjectiveChart extends Chart
{
    public function __construct(protected int $value, protected ?int $objective = null)
    {
    }

    public function serialize()
    {
        $result = ['value' => $this->value];

        if ($this->objective) {
            $result['objective'] = $this->objective;
        }

        return $result;
    }
}
