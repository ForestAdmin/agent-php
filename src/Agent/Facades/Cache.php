<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;

/**
 * Class Cache
 *
 * @method static array get($key)
 * @method static array put($key, $value, $seconds)
 *
 * @see CacheServices
 */
class Cache extends Facade
{
    public static function getFacadeObject()
    {
        $filesystem = new Filesystem();
        $directory = __DIR__ . '/../cache'; // todo update path with APP DIR

        return new CacheServices($filesystem, $directory);
    }
}
