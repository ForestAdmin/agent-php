<?php

namespace ForestAdmin;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;

if (! function_exists(__NAMESPACE__ . '\httpDriver')) {
    function httpDriver(): ?ForestAdminHttpDriver
    {
        return cache('httpDriver');
    }
}

if (! function_exists(__NAMESPACE__ . '\cache')) {
    function cache(string $key, $value = null, ?int $ttl = 60)
    {
        $container = AgentFactory::getContainer();
        if ($value !== null) {
            return $container->get('cache')->put($key, $value, $ttl);
        }

        return $container->get('cache')->get($key);
    }
}

if (! function_exists(__NAMESPACE__ . '\config')) {
    /**
     * @throws ErrorException
     */
    function config(?string $key = null)
    {
        $config = cache('config');

        if ($key === null) {
            return $config;
        }

        if (array_key_exists($key, $config)) {
            return $config[$key];
        }

        return null;
    }
}
