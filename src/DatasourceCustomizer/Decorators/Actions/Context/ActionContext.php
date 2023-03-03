<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\Context;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Actions\ActionCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Projection\Projection;

class ActionContext
{
    private array $queries;

    private Projection $projection;

    public function __construct(
        protected ActionCollection $collection,
        protected Caller $caller,
        protected ?array $formValues,
        protected ?Filter $filter = null,
        protected array $used = []
    ) {
    }
}
