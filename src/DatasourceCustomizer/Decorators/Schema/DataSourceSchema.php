<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Schema;

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
