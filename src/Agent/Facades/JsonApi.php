<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use ForestAdmin\AgentPHP\Agent\Services\JsonApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Cache
 *
 * @method static array render($class, string $name, array $metadata = [])
 * @method static array renderItem($class, string $name, string $transformer)
 * @method static JsonResponse deactivateCountResponse()
 *
 * @see JsonApiResponse
 */
class JsonApi extends Facade
{
    public static function getFacadeObject()
    {
        return new JsonApiResponse();
    }
}
