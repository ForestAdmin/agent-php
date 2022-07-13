<?php

namespace ForestAdmin\AgentPHP\Agent\Facades;

use ForestAdmin\AgentPHP\Agent\Services\JsonApiResponse;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class Cache
 *
 * @method static array render($class, TransformerAbstract $transformer, string $name, array $metadata = [])
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
