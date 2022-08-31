<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Security;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractAuthenticatedRoute;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ScopeInvalidation extends AbstractAuthenticatedRoute
{
    /**
     * @return $this
     */
    public function setupRoutes(): self
    {
        $this->addRoute(
            'scope-invalidation',
            'post',
            '/scope-cache-invalidation',
            fn () => $this->handleRequest()
        );

        return $this;
    }

    public function handleRequest()
    {
        $this->build();
        $renderingId = $this->request->input('renderingId');
        if (! is_numeric($renderingId)) {
            throw new ForestException('Malformed body');
        }
        $this->permissions->invalidateCache($renderingId);

        return [
            'content' => null,
            'status'  => 204,
        ];
    }
}
