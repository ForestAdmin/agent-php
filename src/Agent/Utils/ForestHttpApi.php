<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

class ForestHttpApi
{
    public static function hasSchema(array $httpOptions, string $hash): bool
    {
//        try {
//            const response = await superagent
//            .post(new URL('/forest/apimaps/hashcheck', options.forestServerUrl).toString())
//            .send({ schemaFileHash: hash })
//            .set('forest-secret-key', options.envSecret);
//
//          return !response?.body?.sendSchema;
//        } catch (e) {
//            this.handleResponseError(e);
//        }

        return true;
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
            dd($e);
        }
    }

    public static function getPermissions(int $renderingId)
    {
        try {
            $forestApi = new ForestApiRequester();
            $response = $forestApi->get(
                '/liana/v3/permissions',
                compact('renderingId')
            );
            $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($body['meta']['rolesACLActivated']) && ! $body['meta']['rolesACLActivated']) {
                throw new ForestException('Roles V2 are unsupported');
            }

            return $body;
        } catch (\Exception $e) {
            // todo this.handleResponseError(e);
        }
    }
}
