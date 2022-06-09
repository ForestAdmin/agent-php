<?php

namespace ForestAdmin\AgentPHP\Agent\Services;


class Serializer
{
    private string $prefix;

    public function __construct(protected array $options)
    {
        $this->prefix = $options['prefix'];
    }
}
