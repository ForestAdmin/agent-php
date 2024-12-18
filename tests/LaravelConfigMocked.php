<?php

namespace ForestAdmin\AgentPHP\Tests;

class LaravelConfigMocked
{
    public static array $config = [
        'database.default'            => 'sqlite',
        'database.connections.sqlite' => [
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/Datasets/database.sqlite',
            'prefix'   => '',
        ],
    ];

    public static function get($key)
    {
        return self::$config[$key];
    }
}
