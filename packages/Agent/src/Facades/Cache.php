<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use Closure;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;

/**
 * Class Cache
 *
 * @method static array get($key)
 * @method static array put($key, $value, $seconds)
 * @method static array remember($key, Closure $callback, $seconds)
 * @method static bool forget($key)
 *
 * @see CacheServices
 */
class Cache extends Facade
{
    public static function getFacadeObject()
    {
        $container = AgentFactory::getContainer();

        return $container->get('cache');
    }
}
