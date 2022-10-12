<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ErrorException;

use function ForestAdmin\config;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;

class ForestApiRequester
{
    private array $headers;

    private Client $client;

    /**
     * ForestApiRequester constructor
     *
     * @throws ErrorException
     */
    public function __construct()
    {
        $this->headers = [
            'Content-Type'      => 'application/json',
            'forest-secret-key' => config('envSecret'),
        ];
        $this->client = new Client();
    }

    /**
     * @param string $route
     * @param array  $query
     * @param array  $headers
     * @return Response
     * @throws GuzzleException
     */
    public function get(string $route, array $query = [], array $headers = []): Response
    {
        $url = $this->makeUrl($route);
        $params = $this->getParams($query, [], $this->headers($headers));

        return $this->call('get', $url, $params);
    }

    /**
     * @param string $route
     * @param array  $query
     * @param array  $body
     * @param array  $headers
     * @return Response
     * @throws GuzzleException
     */
    public function post(string $route, array $query = [], array $body = [], array $headers = []): Response
    {
        $url = $this->makeUrl($route);
        $params = $this->getParams($query, $body, $this->headers($headers));

        return $this->call('post', $url, $params);
    }

    /**
     * @param array $query
     * @param array $body
     * @param array $headers
     * @return array[]
     * @throws ErrorException
     */
    public function getParams(array $query = [], array $body = [], array $headers = []): array
    {
        return [
            'headers' => $headers,
            'query'   => $query,
            'json'    => $body,
            'verify'  => ! config('debug'),
        ];
    }

    /**
     * @param ClientInterface $client
     * @return void
     */
    public function setClient(ClientInterface $client): void
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $params
     * @return Response
     * @throws GuzzleException
     */
    private function call(string $method, string $url, array $params = []): Response
    {
        try {
            $client = $this->client;
            $response = $client->request($method, $url, $params);
        } catch (\Exception $e) {
            $this->throwException("Cannot reach Forest API at $url, it seems to be down right now");
        }

        return $response;
    }

    /**
     * @param array $headers
     * @return array
     */
    private function headers(array $headers = []): array
    {
        $this->headers = array_merge(
            $this->headers,
            $headers
        );

        return $this->headers;
    }

    /**
     * @param string $route
     * @return string
     * @throws InvalidUrlException
     * @throws ErrorException
     */
    private function makeUrl(string $route): string
    {
        if (! str_starts_with($route, 'https://')) {
            $route = config('forestServerUrl') . $route;
        }

        if (! config('debug')) {
            $this->validateUrl($route);
        }

        return $route;
    }

    /**
     * Verify whether url is correct
     *
     * @param string $url
     * @return bool
     * @throws \ErrorException
     */
    protected function validateUrl(string $url): bool
    {
        if ((bool) filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) !== true) {
            throw new \ErrorException("$url seems to be an invalid url");
        }

        return true;
    }

    /**
     * @param $message
     * @return void
     * @throws ErrorException
     */
    private function throwException($message): void
    {
        throw new ErrorException($message);
    }
}
