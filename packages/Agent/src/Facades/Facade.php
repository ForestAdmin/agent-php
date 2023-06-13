<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
abstract class Facade
{
    /**
     * Get the registered object of the component.
     *
     * @throws \RuntimeException
     */
    public static function getFacadeObject()
    {
        throw new RuntimeException('Facade does not implement getFacadeObject method.');
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array  $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeObject();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
