<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Schema;

use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\ActionScope;

class ActionSchema
{
    public function __construct(
        protected ActionScope $scope,
        protected bool $generateFile = false,
        protected bool $staticForm = false
    ) {
    }
}
