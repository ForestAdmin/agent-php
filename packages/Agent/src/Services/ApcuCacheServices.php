<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use Closure;
use ForestAdmin\AgentPHP\Agent\Contracts\Cache;
use Illuminate\Support\Str;

/**
 * @codeCoverageIgnore
 */
class ApcuCacheServices implements Cache
{
    public function __construct(protected string $prefix = 'forest_cache')
    {
    }

    /**
     * @param  string|array  $key
     * @return bool
     */
    public function has($key)
    {
        return apcu_exists($this->formatKey($key));
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array  $key
     * @return mixed
     */
    public function get($key)
    {
        return apcu_fetch($this->formatKey($key));
    }

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  int|null    $seconds
     * @return bool
     */
    public function put($key, $value, ?int $seconds = 0)
    {
        return apcu_store($this->formatKey($key), $value, $seconds);
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     * @param         $key
     * @param Closure $callback
     * @param         $ttl
     * @return bool
     */
    public function remember($key, Closure $callback, $ttl)
    {
        return apcu_entry($this->formatKey($key), $callback, $ttl);
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds)
    {
        return apcu_add($this->formatKey($key), $value, $seconds);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        return apcu_delete($this->formatKey($key));
    }

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        foreach (apcu_cache_info()['cache_list'] as $value) {
            if(Str::startsWith($value['info'], $this->prefix)) {
                $this->forget($value['info']);
            }
        }
    }

    private function formatKey($key): string
    {
        return $this->prefix . '_' . $key;
    }
}
