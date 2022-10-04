<?php

namespace ForestAdmin\AgentPHP\Agent\Auth;

use ForestAdmin\AgentPHP\Agent\Auth\OAuth2\ForestProvider;
use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use ForestAdmin\AgentPHP\Agent\Utils\Traits\FormatGuzzle;
use function ForestAdmin\cacheRemember;
use function ForestAdmin\config;
use function ForestAdmin\cache;
use GuzzleHttp\Exception\GuzzleException;

class OidcClientManager
{
    use FormatGuzzle;

    public const TTL = 60 * 60 * 24;

    protected ForestApiRequester $forestApi;

    public function __construct()
    {
        $this->forestApi = new ForestApiRequester();
    }

    /**
     * @return ForestProvider|string
     * @throws GuzzleException|\ErrorException
     */
    public function makeForestProvider(): ForestProvider|string
    {
        $cacheKey = config('envSecret') . '-client-data';

        try {
            $config = $this->retrieve();
            cacheRemember(
                $cacheKey,
                function () use ($config) {
                    $clientCredentials = $this->register(
                        [
                            'token_endpoint_auth_method' => 'none',
                            'registration_endpoint'      => $config['registration_endpoint'],
                            'application_type'           => 'web',
                        ]
                    );

                    return [
                        'client_id'    => $clientCredentials['client_id'],
                        'issuer'       => $config['issuer'],
                        'redirect_uri' => $clientCredentials['redirect_uris'][0],
                    ];
                },
                self::TTL
            );
        } catch (\Exception $e) {
            throw new \ErrorException(ErrorMessages::REGISTRATION_FAILED);
        }

        return new ForestProvider(
            cache($cacheKey)['issuer'],
            [
                'clientId'    => cache($cacheKey)['client_id'],
                'redirectUri' => cache($cacheKey)['redirect_uri'],
                'envSecret'   => config('envSecret'),
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
