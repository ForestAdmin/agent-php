<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

class ForestAdminHttpDriverServices
{
    protected Permissions $permissions;

    protected Serializer $serializer;

    public function __construct(protected array $options)
    {
        $this->permissions = new Permissions($options);
        $this->serializer = new Serializer($options);
    }
}
