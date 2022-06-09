<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit\Components;

class Caller
{
    public function __construct(
        protected int $id,
        protected string $email,
        protected string $firstName,
        protected string $lastName,
        protected string $team,
        protected int $renderingId,
        protected string $role,
        protected array $tags,
        protected string $timezone,
    ) {
    }
}
