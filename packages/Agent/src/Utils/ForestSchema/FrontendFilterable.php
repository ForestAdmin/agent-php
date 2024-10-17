<?php

namespace ForestAdmin\AgentPHP\Agent\Utils\ForestSchema;

final class FrontendFilterable
{
    public static function isFilterable($operator): bool
    {
        return $operator && ! empty($operator);
    }
}
