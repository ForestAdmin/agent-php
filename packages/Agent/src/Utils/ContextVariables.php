<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use Illuminate\Support\Str;

class ContextVariables
{
    public const USER_VALUE_PREFIX = 'currentUser.';

    public const USER_VALUE_TAG_PREFIX = 'currentUser.tags.';

    public const USER_VALUE_TEAM_PREFIX = 'currentUser.team.';

    public function __construct(private array $team, private array $user, private ?array $requestContextVariables = null)
    {
    }

    public function getValue($contextVariableKey)
    {
        if (Str::startsWith($contextVariableKey, $this::USER_VALUE_PREFIX)) {
            return $this->getCurrentUserData($contextVariableKey);
        }

        return $this->requestContextVariables[$contextVariableKey];
    }

    private function getCurrentUserData(string $contextVariableKey)
    {
        if (Str::startsWith($contextVariableKey, $this::USER_VALUE_TEAM_PREFIX)) {
            return $this->team[substr($contextVariableKey, strlen($this::USER_VALUE_TEAM_PREFIX))];
        }

        if (Str::startsWith($contextVariableKey, $this::USER_VALUE_TAG_PREFIX)) {
            return $this->user['tags'][substr($contextVariableKey, strlen($this::USER_VALUE_TAG_PREFIX))];
        }

        return $this->user[substr($contextVariableKey, strlen($this::USER_VALUE_PREFIX))];
    }
}
