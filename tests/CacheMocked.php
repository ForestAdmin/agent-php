<?php

namespace ForestAdmin\AgentPHP\Tests;

use Closure;
use ForestAdmin\AgentPHP\Agent\Contracts\Cache;

class CacheMocked implements Cache
{
    public static array $cache = [];

    public function get($key)
    {
        return self::$cache[$key] ?? null;
    }

    public function put($key, $value, ?int $seconds = 0)
    {
        self::$cache[$key] = $value;
    }

    public function forget($key)
    {
        unset(self::$cache[$key]);
    }

    public function flush()
    {
        self::$cache = [];
    }

    public function has($key)
    {
        return isset(self::$cache[$key]);
    }

    public function remember($key, Closure $callback, $ttl)
    {
        if (! isset(self::$cache[$key])) {
            $result = $callback();
            $this->put($key, $result, $ttl);
        }

        return self::$cache[$key];
    }

    public function add($key, $value, $seconds)
    {
        if (! isset(self::$cache[$key])) {
            $this->put($key, $value, $seconds);
        }
    }
}
