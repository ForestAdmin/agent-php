<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class ForestController
{
    public const ROUTE_CHARTS_PREFIX = '/forest/_charts';

    /**
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function __invoke(Request $request): JsonResponse|Response
    {
        $route = $request->get('_route');
        $params = $request->get('_route_params');
        $routes = Router::getRoutes();

        try {
            return $this->response(call_user_func($routes[$route]['closure'], $params));
        } catch (Throwable $exception) {
            return $this->exceptionHandler($exception);
        }
    }

    protected function response(array $data): Response|JsonResponse
    {
        if (isset($data['headers']['Content-type']) && $data['headers']['Content-type'] === 'text/csv') {
            return new Response($data['content'], $data['status'] ?? 200, $data['headers']);
        }

        if (isset($data['is_action'])) {
            unset($data['is_action']);

            if ($data['type'] === 'File') {
                $response = new BinaryFileResponse($data['stream'], 200, ['Content-Type' => $data['mimeType'], 'Access-Control-Expose-Headers' => 'Content-Disposition']);
                $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $data['name']
                );

                return $response;
            }

            return new JsonResponse($data, $data['status'] ?? 200);
        }

        return new JsonResponse($data['content'], $data['status'] ?? 200, $data['headers'] ?? []);
    }

    protected function exceptionHandler(Throwable $exception): JsonResponse|Throwable
    {
        if ($exception instanceof ForestValidationException) {
            $data = [
                'errors' => [
                    [
                        'name'   => 'ForestValidationException',
                        'detail' => $exception->getMessage(),
                        'status' => 400,
                    ],
                ],
            ];

            return new JsonResponse($data, 400);
        }

        return $exception;
    }
}
