<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Security;

use ErrorException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Auth\AuthManager;
use ForestAdmin\AgentPHP\Agent\Auth\OidcClientManager;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use function ForestAdmin\config;

class Authentication extends AbstractRoute
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setupRoutes(): self
    {
        $this->addRoute(
            'forest.authentication',
            'POST',
            '/authentication',
            fn () => $this->handleAuthentication()
        );

        $this->addRoute(
            'forest.authentication-callback',
            'GET',
            '/authentication/callback',
            fn () => $this->handleAuthenticationCallback()
        );

        $this->addRoute(
            'forest.logout',
            'POST',
            '/authentication/logout',
            fn () => $this->handleAuthenticationLogout()
        );

        return $this;
    }

    /**
     * @return array[]
     * @throws ErrorException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function handleAuthentication(): array
    {
        $renderingId = $this->getAndCheckRenderingId();

        return [
            'content' => [
                'authorizationUrl' => $this->auth()->start($renderingId),
            ],
        ];
    }

    /**
     * @return array[]
     * @throws ErrorException
     * @throws GuzzleException
     * @throws IdentityProviderException
     * @throws JsonException
     */
    public function handleAuthenticationCallback(): array
    {
        $token = $this->auth()->verifyCodeAndGenerateToken($this->request->all());
        $tokenData = JWT::decode($token, new Key(config('envSecret'), 'HS256'));

        return [
            'content' => [
                'token'     => $token,
                'tokenData' => $tokenData,
            ],
        ];
    }

    public function handleAuthenticationLogout()
    {
        return [
            'content' => null,
            'status'  => 204,
        ];
    }

    /**
     * @codeCoverageIgnore
     * @return AuthManager
     */
    public function auth(): AuthManager
    {
        return new AuthManager(new OidcClientManager());
    }

    /**
     * @return int
     * @throws ErrorException
     */
    protected function getAndCheckRenderingId(): int
    {
        if (! $renderingId = $this->request->get('renderingId')) {
            throw new ErrorException(ErrorMessages::MISSING_RENDERING_ID);
        }

        if (! is_numeric($renderingId)) {
            throw new ErrorException(ErrorMessages::INVALID_RENDERING_ID);
        }

        return (int)$renderingId;
    }
}
