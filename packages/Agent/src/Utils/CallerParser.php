<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

use function ForestAdmin\config;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CallerParser
{
    public function __construct(
        protected Request $request,
    ) {
    }

    public function parse(): Caller
    {
        $this->validateHeaders();
        $params = $this->decodeToken();
        $params['timezone'] = $this->extractTimezone();
        $params['request'] = [ 'ip' => $this->request->getClientIp()];
        [$project, $environment] = $this->extractForestContext();
        $params['project'] = $project;
        $params['environment'] = $environment;

        return Caller::makeFromRequestData($params);
    }

    private function validateHeaders(): void
    {
        if (! $this->request->bearerToken()) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'You must be logged in to access at this resource.');
        }
    }

    private function decodeToken(): array
    {
        $decoded = (array) JWT::decode($this->request->bearerToken(), new Key(config('authSecret'), 'HS256'));
        unset($decoded['exp']);

        if (isset($decoded['tags']) && $decoded['tags'] instanceof \stdClass) {
            $decoded['tags'] = (array) $decoded['tags'];
        }

        return $decoded;
    }

    private function extractTimezone(): string
    {
        $timezone = $this->request->get('timezone');

        if (! $timezone) {
            throw new ForestException('Missing timezone');
        }

        if (! in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            throw new ForestException("Invalid timezone: $timezone");
        }

        return $timezone;
    }

    private function extractForestContext(): array
    {
        $forestContextUrl = $this->request->header('forest-context-url');

        if (preg_match('/https:\/\/[^\/]*\/([^\/]*)\/([^\/]*)/', $forestContextUrl, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return [null, null];
    }
}
