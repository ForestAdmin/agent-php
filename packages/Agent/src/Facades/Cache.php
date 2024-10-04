<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use Closure;
use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\ApcuCacheServices;
use ForestAdmin\AgentPHP\Agent\Services\FileCacheServices;

/**
 * Class Cache
 *
 * @method static get($key)
 * @method static put($key, $value, $seconds)
 * @method static add($key, $value, $seconds)
 * @method static remember($key, Closure $callback, $seconds)
 * @method static bool forget($key)
 *
 * @see ApcuCacheServices
 */
class Cache extends Facade
{
    public static function getFacadeObject()
    {
        if (self::apcuEnabled()) {
            return new ApcuCacheServices();
        }

        $cacheOptions = AgentFactory::getFileCacheOptions();

        return new FileCacheServices($cacheOptions['filesystem'], $cacheOptions['directory']);
    }

    public static function apcuEnabled(): bool
    {
        $cacheOptions = AgentFactory::getFileCacheOptions();

        return (extension_loaded('apcu') && ini_get('apcu.enabled'))
            && $cacheOptions['disabledApcuCache'] ?? false;
    }

    public static function enabled(): bool
    {
        return self::apcuEnabled() || AgentFactory::getFileCacheOptions() !== null;
    }
}
