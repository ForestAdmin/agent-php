<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use GuzzleHttp\Client;

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
}
