<?php

namespace ForestAdmin;

use ForestAdmin\AgentPHP\Agent\Facades\Cache;

// @codeCoverageIgnoreStart

if (! function_exists(__NAMESPACE__ . '\cache')) {
    function cache(string $key, $value = null, ?int $ttl = 60)
    {
        if ($value !== null) {
            return Cache::put($key, $value, $ttl);
        }

        return Cache::get($key);
    }
}

if (! function_exists(__NAMESPACE__ . '\forget')) {
    function forget(string $key)
    {
        return Cache::forget($key);
    }
}

if (! function_exists(__NAMESPACE__ . '\cacheRemember')) {
    function cacheRemember(string $key, \Closure $callback, ?int $ttl = 60)
    {
        return Cache::remember($key, $callback, $ttl);
    }
}

if (! function_exists(__NAMESPACE__ . '\config')) {
    function config(?string $key = null)
    {
        $config = cache('config');

        if ($key === null) {
            return $config;
        }

        if (is_array($config) && array_key_exists($key, $config)) {
            return $config[$key];
        }

        return null;
    }
}

// @codeCoverageIgnoreEnd
