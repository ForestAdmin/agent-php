<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\ActionScope;

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
