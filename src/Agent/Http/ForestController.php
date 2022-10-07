<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ForestController
{
    public const ROUTE_CHARTS_PREFIX = '/forest/_charts';

    /**
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function __invoke(Request $request)
    {
        $route = $request->attributes->get('_route');
        $params = $request->attributes->get('_route_params');

        $routes = Router::getRoutes();
        $data = call_user_func($routes[$route]['closure'], $params);

        if (isset($data['headers']['Content-type']) && $data['headers']['Content-type'] === 'text/csv') {
            return new Response($data['content'], $data['status'] ?? 200, $data['headers'] ?? []);
        }

        return new JsonResponse($data['content'], $data['status'] ?? 200, $data['headers'] ?? []);
    }
}
