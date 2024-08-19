<?php

namespace ForestAdmin\AgentPHP\Agent\Contracts;

use Closure;

interface Cache
{
    /**
     * Retrieve an item from the cache by key.
     *
     * @param string|array $key
     * @return mixed
     */
    public function get($key);

    /**
     * Store an item in the cache for a given number of seconds.
     *
     * @param string   $key
     * @param mixed    $value
     * @param int|null $seconds
     * @return bool
     */
    public function put($key, $value, ?int $seconds);

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget($key);

    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush();

    /**
     * @param  string|array  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     * @param         $key
     * @param Closure $callback
     * @param         $ttl
     * @return bool
     */
    public function remember($key, Closure $callback, $ttl);

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     * @return bool
     */
    public function add($key, $value, $seconds);
}
