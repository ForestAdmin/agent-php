<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Str;

class ForestHttpApi
{
    public static function hasSchema(string $schemaFileHash): bool
    {
        try {
            $forestApi = new ForestApiRequester();
            $response = $forestApi->post(
                '/forest/apimaps/hashcheck',
                [],
                compact('schemaFileHash'),
            );
            $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return $body['sendSchema'];
        } catch (\Exception $e) {
            self::handleResponseError($e);
        }
    }

    public static function uploadSchema(array $jsonApiDocument): void
    {
        try {
            $forestApi = new ForestApiRequester();
            $forestApi->post(
                '/forest/apimaps',
                [],
                $jsonApiDocument
            );
        } catch (\Exception $e) {
            self::handleResponseError($e);
        }
    }

    /**
     * @throws ForestException
     */
    public static function handleResponseError($e): void
    {
        if ($e instanceof ForestException) {
            throw $e;
        }

        if (Str::contains($e->getMessage(), 'certificate')) {
            throw new ForestException('ForestAdmin server TLS certificate cannot be verified. Please check that your system time is set properly.');
        }

        if ($e->getCode() === 0 || $e->getCode() === 502) {
            throw new ForestException('Failed to reach ForestAdmin server. Are you online?');
        }

        if ($e->getCode() === 404) {
            throw new ForestException('ForestAdmin server failed to find the project related to the envSecret you configured. Can you check that you copied it properly in the Forest initialization?');
        }

        if ($e->getCode() === 503) {
            throw new ForestException('Forest is in maintenance for a few minutes. We are upgrading your experience in the forest. We just need a few more minutes to get it right.');
        }

        throw new ForestException('An unexpected error occured while contacting the ForestAdmin server. Please contact support@forestadmin.com for further investigations.');
    }
}
