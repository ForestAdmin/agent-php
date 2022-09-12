<?php

namespace ForestAdmin\AgentPHP\Agent\Auth;

use ForestAdmin\AgentPHP\Agent\Utils\Traits\FormatGuzzle;
use ForestAdmin\LaravelForestAdmin\Utils\ErrorMessages;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JsonException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use function ForestAdmin\config;

class AuthManager
{
    use FormatGuzzle;

    private OidcClientManager $oidc;

    public function __construct()
    {
        $this->oidc = new OidcClientManager();
    }

    /**
     * @param int    $renderingId
     * @return string
     * @throws GuzzleException
     * @throws JsonException|\ErrorException
     */
    public function start(int $renderingId): string
    {
        $client = $this->oidc->makeForestProvider();

        return $client->getAuthorizationUrl(
            [
                'state' => json_encode(compact('renderingId'), JSON_THROW_ON_ERROR),
            ]
        );
    }

    /**
     * @param array  $params
     * @return string
     * @throws JsonException
     * @throws IdentityProviderException
     * @throws GuzzleException|\ErrorException
     */
    public function verifyCodeAndGenerateToken(array $params): string
    {
        $this->stateIsValid($params);

        $forestProvider = $this->oidc->makeForestProvider();
        $forestProvider->setRenderingId($this->getRenderingIdFromState($params['state']));
        if (config('debug')) {
            // @codeCoverageIgnoreStart
            $guzzleClient = new Client([RequestOptions::VERIFY => false]);
            $forestProvider->setHttpClient($guzzleClient);
            // @codeCoverageIgnoreEnd
        }
        $accessToken = $forestProvider->getAccessToken(
            'authorization_code',
            [
                'code'          => $params['code'],
                'response_type' => 'token',
            ]
        );

        $resourceOwner = $forestProvider->getResourceOwner($accessToken);

        return $resourceOwner->makeJwt();
    }

    /**
     * @param string $state
     * @return int
     * @throws JsonException
     * @throws \ErrorException
     */
    private function getRenderingIdFromState(string $state): int
    {
        $state = json_decode($state, true, 512, JSON_THROW_ON_ERROR);
        $renderingId = $state['renderingId'];

        if (! (is_string($renderingId) || is_int($renderingId))) {
            throw new \ErrorException(ErrorMessages::INVALID_STATE_FORMAT);
        }

        return (int) $renderingId;
    }

    /**
     * @param array $params
     * @return bool
     * @throws JsonException
     * @throws \ErrorException
     */
    private function stateIsValid(array $params): bool
    {
        if (! array_key_exists('state', $params)) {
            throw new \ErrorException(ErrorMessages::INVALID_STATE_MISSING);
        }

        if (! array_key_exists('renderingId', json_decode($params['state'], true, 512, JSON_THROW_ON_ERROR))) {
            throw new \ErrorException(ErrorMessages::INVALID_STATE_RENDERING_ID);
        }

        return true;
    }
}
