<?php

namespace ForestAdmin\AgentPHP\Agent\Http;

use ForestAdmin\AgentPHP\Agent\Http\Traits\ErrorHandling;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;

class ForestController
{
    use ErrorHandling;
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
        $data = [
            'errors' => [
                [
                    'name'   => $this->getErrorName($exception),
                    'detail' => $this->getErrorMessage($exception),
                    'status' => $this->getErrorStatus($exception),
                ],
            ],
        ];

        if (method_exists($exception, 'getData')) {
            $data['errors'][0]['data'] = $exception->getData();
        }

        return new JsonResponse($data, $this->getErrorStatus($exception),  $this->getErrorHeaders($exception));
    }

    /**
     * @param string $routeName
     * @return \Closure
     * @codeCoverageIgnore
     */
    protected function getClosure(string $routeName): \Closure
    {
        $routes = Router::getRoutes();

        return $routes[$routeName]['closure'];
    }
}
