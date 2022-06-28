<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Auth\AuthManager;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;

class Authentication extends AbstractRoute
{
    private AuthManager $auth;

    public function __construct(protected ForestAdminHttpDriverServices $services, protected array $options)
    {
        parent::__construct($services, $options);
        $this->auth = new AuthManager($this->options);
    }

    public function setupRoutes(): self
    {
        $this->addRoute(
            'authentication',
            'POST',
            '/authentication',
            fn () => $this->handleAuthentication()
        );

        $this->addRoute(
            'authentication-callback',
            'GET',
            '/authentication/callback',
            fn () => $this->handleAuthenticationCallback()
        );

        $this->addRoute(
            'logout',
            'POST',
            '/authentication/logout',
            fn () => $this->handleAuthenticationLogout()
        );

        return $this;
    }

    /**
     * @throws \ErrorException
     */
    public function handleAuthentication()
    {
        $request = Request::createFromGlobals();
        $renderingId = $this->getAndCheckRenderingId($request);

        return [
            'status'           => 200,
            'authorizationUrl' => $this->auth->start($this->options['agentUrl'] . '/forest/authentication/callback', $renderingId),
        ];
    }

    public function handleAuthenticationCallback()
    {
        $request = Request::createFromGlobals();
        $token = $this->auth->verifyCodeAndGenerateToken($this->options['agentUrl'] .  '/forest/authentication/callback', $request->all());
        $tokenData = JWT::decode($token, new Key($this->options['envSecret'], 'HS256'));

        return [
            'status'           => 200,
            'token'            => $token,
            'tokenData'        => $tokenData,
        ];
    }

    public function handleAuthenticationLogout()
    {
        // todo return 204
    }

    /**
     * @param Request $request
     * @return int
     * @throws \ErrorException
     */
    private function getAndCheckRenderingId(Request $request): int
    {
        if (! $renderingId = $request->get('renderingId')) {
            throw new \ErrorException(ErrorMessages::MISSING_RENDERING_ID);
        }

        if (! (is_string($renderingId) || is_int($renderingId))) {
            throw new \ErrorException(ErrorMessages::INVALID_RENDERING_ID);
        }

        return (int) $renderingId;
    }
}
