<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook;

use Closure;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\HookContext;

class Hooks
{
    protected array $before = [];
    protected array $after = [];

    public function executeBefore(HookContext $context): void
    {
        foreach ($this->before as $hookClosure) {
            $hookClosure($context);
        }
    }

    public function executeAfter(HookContext $context): void
    {
        foreach ($this->after as $hookClosure) {
            $hookClosure($context);
        }
    }

    public function addHandler(string $position, Closure $closure): void
    {
        if ($position === 'After') {
            $this->after[] = $closure;
        } else {
            $this->before[] = $closure;
        }
    }
}
