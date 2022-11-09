<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

class DataSourceSchema
{
    /**
     * @param string[] $charts
     */
    public function __construct(
        protected array $charts = []
    ) {
    }
}
