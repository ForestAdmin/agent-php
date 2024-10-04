<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Security;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;

class ScopeInvalidation extends AbstractAuthenticatedRoute
{
    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest.scope-invalidation',
            'post',
            '/scope-cache-invalidation',
            fn () => $this->handleRequest()
        );

        return $this;
    }

    public function handleRequest()
    {
        $this->build();
        $this->permissions->invalidateCache('forest.scopes');

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
