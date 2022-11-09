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

    public function getScope(): ActionScope
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isGenerateFile(): bool
    {
        return $this->generateFile;
    }

    /**
     * @return bool
     */
    public function isStaticForm(): bool
    {
        return $this->staticForm;
    }
}
