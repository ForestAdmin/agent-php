<?php

namespace ForestAdmin\AgentPHP\Agent\Routes;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Services\IpWhitelist;
use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        $this->checkIp(new ForestApiRequester());
        $this->caller = QueryStringParser::parseCaller($this->request);
        $this->permissions = new Permissions($this->caller);
    }

    public function checkIp(ForestApiRequester $forestApiRequester): void
    {
        $ipWhitelist = new IpWhitelist($forestApiRequester);
        if ($ipWhitelist->isEnabled()) {
            $ip = $this->request->getClientIp();
            if (! $ipWhitelist->isIpMatchesAnyRule($ip)) {
                throw new HttpException(Response::HTTP_FORBIDDEN, "IP address rejected ($ip)");
            }
        }
    }
}
