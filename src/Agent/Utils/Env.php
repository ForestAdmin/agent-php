<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

/**
 * @codeCoverageIgnore
 */
class Env
{
    public static function get(string $key, $defaultValue = null)
    {
        return array_key_exists($key, $_ENV) ? self::castValue($_ENV[$key]) : self::castValue($defaultValue);
    }

    private static function castValue($value)
    {
        if ($value === 'true') {
            return true;
        } elseif ($value === 'false') {
            return false;
        } else {
            return $value;
        }
    }
}
