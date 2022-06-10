<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

abstract class Chart
{
    /**
     * @return mixed
     */
    abstract public function serialize(array $data);
}
