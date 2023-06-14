<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Services\Permissions;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;

class ContextVariablesInstantiator
{
    public static function buildContextVariables(Caller $caller, array $requestContextVariables): ContextVariables
    {
        $permissions = new Permissions($caller);
        $user = $permissions->getUserData($caller->getId());
        $team = $permissions->getTeam($caller->getRenderingId());

        return new ContextVariables($team, $user, $requestContextVariables);
    }
}
