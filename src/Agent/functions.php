<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;

if (! function_exists('forest_cache')) {
    function forest_cache(string $key, $value = null, ?int $ttl = 60)
    {
        $container = AgentFactory::getContainer();
        if ($value !== null) {
            return $container->get('cache')->put($key, $value, $ttl);
        }

        return $container->get('cache')->get($key);
    }
}

if (! function_exists('forest_config')) {
    /**
     * @throws ErrorException
     */
    function forest_config(?string $key = null)
    {
        $config = forest_cache('config');

        if ($key === null) {
            return $config;
        }

        if (array_key_exists($key, $config)) {
            return $config[$key];
        }

        throw new ErrorException('undefined config key '. $key);
    }
}
