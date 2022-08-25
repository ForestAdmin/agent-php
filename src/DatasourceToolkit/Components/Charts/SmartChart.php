<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class SmartChart extends Chart
{
    public function __construct(protected $data)
    {
    }

    public function serialize()
    {
        return $this->data;
    }
}
