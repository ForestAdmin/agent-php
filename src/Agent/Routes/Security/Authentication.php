<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Security;

use ErrorException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Auth\AuthManager;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use function ForestAdmin\config;

class Authentication extends AbstractRoute
{
    private AuthManager $auth;

    public function __construct(protected ForestAdminHttpDriverServices $services)
    {
        parent::__construct($services);
        $this->auth = new AuthManager();
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
     * @return array
     * @throws ErrorException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function handleAuthentication()
    {
        $request = Request::createFromGlobals();
        $renderingId = $this->getAndCheckRenderingId($request);

        return [
            'status'           => 200,
            'authorizationUrl' => $this->auth->start(config('agentUrl') . '/forest/authentication/callback', $renderingId),
        ];
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     * @throws ErrorException
     * @throws IdentityProviderException
     */
    public function handleAuthenticationCallback()
    {
        $request = Request::createFromGlobals();
        $token = $this->auth->verifyCodeAndGenerateToken(config('agentUrl') .  '/forest/authentication/callback', $request->all());
        $tokenData = JWT::decode($token, new Key(config('envSecret'), 'HS256'));

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
     * @throws ErrorException
     */
    private function getAndCheckRenderingId(Request $request): int
    {
        if (! $renderingId = $request->get('renderingId')) {
            throw new ErrorException(ErrorMessages::MISSING_RENDERING_ID);
        }

        if (! (is_string($renderingId) || is_int($renderingId))) {
            throw new ErrorException(ErrorMessages::INVALID_RENDERING_ID);
        }

        return (int) $renderingId;
    }
}
