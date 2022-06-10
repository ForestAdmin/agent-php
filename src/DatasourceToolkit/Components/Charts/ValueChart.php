<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts;

class ValueChart extends Chart
{
    public function __construct(
        protected int $countCurrent,
        protected int $countPrevious,
    ) {
    }

    /**
     * @return mixed
     */
    public function serialize(array $data)
    {
        return [
            'countCurrent'  => $this->countCurrent,
            'countPrevious' => $this->countPrevious,
        ];
    }
}
