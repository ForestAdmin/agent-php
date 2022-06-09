<?php

use ForestAdmin\AgentPHP\Agent\Builder\Agent;

if (! function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Agent::getContainer();
        }

        return Agent::getContainer()->make($abstract, $parameters);
    }
}

if (! function_exists('cache')) {
    function cache()
    {
        return app('cache');
    }
}
