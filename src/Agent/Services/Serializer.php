<?php

namespace ForestAdmin\AgentPHP\Agent\Services;


use function ForestAdmin\config;

class Serializer
{
    private string $prefix;

    public function __construct()
    {
        $this->prefix = config('prefix');
    }
}
