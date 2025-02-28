<?php

namespace ForestAdmin\AgentPHP\Agent\Auth\OAuth2;

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\AuthenticationOpenIdClient;
use ForestAdmin\AgentPHP\Agent\Utils\ErrorMessages;
use ForestAdmin\AgentPHP\Agent\Utils\Traits\FormatGuzzle;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
  * @codeCoverageIgnore
 */
class ForestProvider extends AbstractProvider
{
    use FormatGuzzle;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $renderingId;

    public function __construct(string $host, private array $options = [])
    {
        parent::__construct($options);
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->host . '/oidc/auth';
    }

    /**
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->host . '/oidc/token';
    }

    /**
     * @param AccessToken $token
     * @return string
     * @throws \Exception
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->host . '/liana/v2/renderings/' . $this->renderingId . '/authorization';
    }

    /**
     * @param int $renderingId
     * @return ForestProvider
     */
    public function setRenderingId(int $renderingId): ForestProvider
    {
        $this->renderingId = $renderingId;

        return $this;
    }

    /**
     * @return string[]
     */
    protected function getRequiredOptions()
    {
        return [
            'urlAuthorize',
            'urlAccessToken',
            'host',
        ];
    }

    /**
     * @return string[]
     */
    protected function getDefaultScopes()
    {
        return ['openid profile email'];
    }

    /**
     * @throws \ErrorException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (404 === $response->getStatusCode()) {
            throw new AuthenticationOpenIdClient(Response::HTTP_NOT_FOUND, ErrorMessages::SECRET_NOT_FOUND);
        } elseif (403 === $response->getStatusCode()) {
            $error = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR)['errors'][0];
            $message = $error['name'] === ErrorMessages::TWO_FACTOR_AUTHENTICATION_REQUIRED ? ErrorMessages::TWO_FACTOR_AUTHENTICATION_REQUIRED : ErrorMessages::AUTHORIZATION_FAILED;

            throw new AuthenticationOpenIdClient(Response::HTTP_FORBIDDEN, $message, $error['detail']);
        } elseif (422 === $response->getStatusCode()) {
            throw new AuthenticationOpenIdClient(Response::HTTP_UNPROCESSABLE_ENTITY, ErrorMessages::SECRET_AND_RENDERINGID_INCONSISTENT);
        } elseif (200 !== $response->getStatusCode()) {
            throw new AuthenticationOpenIdClient($response->getStatusCode(), ErrorMessages::AUTHORIZATION_FAILED);
        }
    }

    /**
     * @param array       $response
     * @param AccessToken $token
     * @return ResourceOwnerInterface|void
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ForestResourceOwner($response, $this->renderingId);
    }

    /**
     * @param AccessToken $token
     * @return array|mixed|void
     * @throws GuzzleException
     * @throws \JsonException
     * @throws \Exception
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        $url = $this->getResourceOwnerDetailsUrl($token);

        $request = $this->getAuthenticatedRequest(
            self::METHOD_GET,
            $url,
            $token,
            [
                'headers' => [
                    'forest-token'      => $token->getToken(),
                    'forest-secret-key' => $this->options['envSecret'],
                ],
            ]
        );

        $response = $this->getParsedResponse($request);
        $userData = $response['data']['attributes'];
        $userData['id'] = $response['data']['id'];

        return $userData;
    }
}
