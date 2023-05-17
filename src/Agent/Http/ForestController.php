<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ForestController
{
    public const ROUTE_CHARTS_PREFIX = '/forest/_charts';

    /**
     * @param Request $request
     * @return JsonResponse|Response
     * @throws Throwable
     */
    public function __invoke(Request $request): JsonResponse|Response
    {
        $route = $request->get('_route');
        $params = $request->get('_route_params');
        $closure = $this->getClosure($route);

        try {
            return $this->response($closure($params));
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

    /**
     * @throws Throwable
     */
    protected function exceptionHandler(Throwable $exception): JsonResponse
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
        } elseif ($exception instanceof HttpException) {
            $data = [
                'errors' => [
                    [
                        'name'   => $exception->getName(),
                        'detail' => $exception->getMessage(),
                        'status' => $exception->getStatusCode(),
                    ],
                ],
            ];

            if (method_exists($exception, 'getData')) {
                $data['errors'][0]['data'] = $exception->getData();
            }

            return new JsonResponse($data, $exception->getStatusCode(), $exception->getHeaders());
        }

        throw $exception;
    }

    protected function getClosure(string $routeName): \Closure
    {
        $routes = Router::getRoutes();

        return $routes[$routeName]['closure'];
    }
}
