<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;

abstract class AbstractAuthenticatedRoute extends AbstractRoute
{
    protected Caller $caller;

    protected Permissions $permissions;

    public function __construct()
    {
        parent::__construct();
    }

    public function build(array $args = []): void
    {
        $this->caller = QueryStringParser::parseCaller($this->request);
        $this->permissions = new Permissions($this->caller);
    }
}
