<?php

namespace ForestAdmin\AgentPHP\Agent\Auth;

use ForestAdmin\AgentPHP\Agent\Auth\OAuth2\ForestProvider;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use ForestAdmin\AgentPHP\Agent\Utils\Traits\FormatGuzzle;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use function ForestAdmin\config;

class OidcClientManager
{
    use FormatGuzzle;

    public const TTL = 60 * 60 * 24;

    private ForestApiRequester $forestApi;

    public function __construct()
    {
        $this->forestApi = new ForestApiRequester();
    }

    /**
     * @param string $callbackUrl
     * @return ForestProvider|string
     * @throws GuzzleException|\ErrorException
     */
    public function getClientForCallbackUrl(string $callbackUrl): ForestProvider|string
    {
//        $cacheKey = $callbackUrl . '-' . config('forest.api.secret') . '-client-data';

        try {
            $config = $this->retrieve();
            $clientCredentials = $this->register(
                [
                    'token_endpoint_auth_method' => 'none',
                    'registration_endpoint'      => $config['registration_endpoint'],
                    'redirect_uris'              => [$callbackUrl],
                    'application_type'           => 'web',
                ]
            );
            $clientData = ['client_id' => $clientCredentials['client_id'], 'issuer' => $config['issuer']];
            // todo improve with cache
        } catch (\Exception $e) {
            throw new \ErrorException(ErrorMessages::REGISTRATION_FAILED);
        }

        return new ForestProvider(
            $clientData['issuer'],
            [
                'clientId'     => $clientData['client_id'],
                'redirectUri'  => $callbackUrl,
                'envSecret'    => config('envSecret'),
            ]
        );
    }

    /**
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     * @throws \ErrorException
     */
    private function retrieve(): array
    {
        try {
            $response = $this->forestApi->get('/oidc/.well-known/openid-configuration');
        } catch (\RuntimeException $e) {
            throw new \ErrorException(ErrorMessages::OIDC_CONFIGURATION_RETRIEVAL_FAILED);
        }

        return $this->getBody($response);
    }

    /**
     * @param array $data
     * @return array
     * @throws GuzzleException
     * @throws \JsonException
     */
    private function register(array $data): array
    {
        $response = $this->forestApi->post(
            $data['registration_endpoint'],
            [],
            $data,
            ['Authorization' => 'Bearer ' . config('envSecret')]
        );

        return $this->getBody($response);
    }
}
