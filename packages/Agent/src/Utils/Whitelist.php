<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Services\IpWhitelist;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait Whitelist
{
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
