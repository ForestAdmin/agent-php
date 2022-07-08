<?php

namespace ForestAdmin\AgentPHP\Agent\Serializer\Scope;

use League\Fractal\Manager;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\ScopeFactoryInterface;

/**
 * @codeCoverageIgnore
 */
class ScopeFactory implements ScopeFactoryInterface
{
    public function createScopeFor(
        Manager $manager,
        ResourceInterface $resource,
        ?string $scopeIdentifier = null
    ): Scope {
        return new Scope($manager, $resource, $scopeIdentifier);
    }

    public function createChildScopeFor(
        Manager                     $manager,
        Scope|\League\Fractal\Scope $parentScope,
        ResourceInterface           $resource,
        ?string                     $scopeIdentifier = null
    ): Scope {
        $scopeInstance = $this->createScopeFor($manager, $resource, $scopeIdentifier);

        // This will be the new children list of parents (parents parents, plus the parent)
        $scopeArray = $parentScope->getParentScopes();
        $scopeArray[] = $parentScope->getScopeIdentifier();

        $scopeInstance->setParentScopes($scopeArray);

        return $scopeInstance;
    }
}
