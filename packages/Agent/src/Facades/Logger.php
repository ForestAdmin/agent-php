<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
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
        $container = AgentFactory::getContainer();

        return $container->get('logger');
    }
}
