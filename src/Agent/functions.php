<?php

namespace ForestAdmin;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;

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

if (! function_exists(__NAMESPACE__ . '\forget')) {
    function forget(string $key)
    {
        $container = AgentFactory::getContainer();

        return $container->get('cache')->forget($key);
    }
}


if (! function_exists(__NAMESPACE__ . '\cacheRemember')) {
    function cacheRemember(string $key, \Closure $callback, ?int $ttl = 60)
    {
        $container = AgentFactory::getContainer();

        return $container->get('cache')->remember($key, $callback, $ttl);
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
