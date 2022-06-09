<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

class Permissions
{
    public function __construct(protected array $options)
    {
    }

    public function invalidateCache(int $renderingId): void
    {
        // todo
        //app('cache')->delete(....)
    }

    public function canChart(): void
    {
        // todo Checks that a charting query is in the list of allowed queries
    }

    public function can(): void
    {
        // todo Check if a user is allowed to perform a specific action
    }

    public function getScope(): void
    {
        // todo
    }

    private function getRenderingPermissions(int $renderingId): array
    {
        //todo
        return [];
    }
}
