<?php

namespace ForestAdmin\AgentPHP\Agent\Utils;

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

    public static function uploadSchema(array $httpOptions, array $jsonApiDocument): void
    {
//    static async uploadSchema(options: HttpOptions, apimap: JSONAPIDocument): Promise<void> {
//        try {
//          await superagent
//            .post(new URL('/forest/apimaps', options.forestServerUrl).toString())
//            .send(apimap)
//            .set('forest-secret-key', options.envSecret);
//        } catch (e) {
//                this.handleResponseError(e);
//    }
//    }
    }
}
