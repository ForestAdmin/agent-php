<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\Agent\Utils\Whitelist;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;

abstract class AbstractAuthenticatedRoute extends AbstractRoute
{
    use Whitelist;

    protected Caller $caller;

    protected Permissions $permissions;

    public function build(array $args = []): void
    {
        $this->checkIp(new ForestApiRequester());
        $this->caller = QueryStringParser::parseCaller($this->request);
        $this->permissions = new Permissions($this->caller);
    }
}
