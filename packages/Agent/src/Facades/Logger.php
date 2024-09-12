<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use ForestAdmin\AgentPHP\Agent\Services\LoggerServices;

/**
 * Class Logger
 *
 * @method static void log(string $level, string $message)
 *
 * @see LoggerServices
 */
class Logger extends Facade
{
    public static function getFacadeObject()
    {
        $serializableLogger = Cache::get('logger');

        return $serializableLogger();
    }
}
