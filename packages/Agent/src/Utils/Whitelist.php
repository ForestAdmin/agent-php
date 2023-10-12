<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Services\IpWhitelist;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Whitelist
{
    public static function checkIp(string $ip): void
    {
        $forestApiRequester = new ForestApiRequester();
        $ipWhitelist = new IpWhitelist($forestApiRequester);
        if ($ipWhitelist->isEnabled()) {
            if (! $ipWhitelist->isIpMatchesAnyRule($ip)) {
                throw new HttpException(Response::HTTP_FORBIDDEN, "IP address rejected ($ip)");
            }
        }
    }
}
