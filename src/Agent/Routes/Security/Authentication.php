<?php

namespace ForestAdmin\AgentPHP\Agent\Routes\Security;

use ForestAdmin\AgentPHP\Agent\Routes\AbstractRoute;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Authentication extends AbstractRoute
{
    public function __construct(protected ForestAdminHttpDriverServices $services, protected array $options)
    {
        parent::__construct($services, $options);
    }

    public function bootstrap(): void
    {
        // Do nothing by default -> maybe this function is not necessary in PHP context
    }

    public function setupRoutes(): array
    {
//        $router->map(
//            ['POST'],
//            '/authentication',
//            fn (Request $request, Response $response) => $this->handleAuthentication($request, $response)
//        );
//
//        $router->map(
//            ['GET'],
//            '/authentication/callback',
//            fn (Request $request, Response $response) => $this->handleAuthenticationCallback($request, $response)
//        );
//
//////        router.use(jwt({ secret: this.options.authSecret, cookie: 'forest_session_token' }));
//
//        $router->map(
//            ['POST'],
//            '/authentication/logout',
//            fn (Request $request, Response $response) => $this->handleAuthentication($request, $response)
//        );

        return [];
    }

    public function handleAuthentication(Request $request, Response $response)
    {
//        const renderingId = Number(context.request.body?.renderingId);
//        this.checkRenderingId(renderingId);
//
//        const authorizationUrl = this.client.authorizationUrl({
//        scope: 'openid email profile',
//        state: JSON.stringify({ renderingId }),
//        });
//
//        context.response.body = { authorizationUrl };
    }

    public function handleAuthenticationCallback(Request $request, Response $response)
    {
    }

    public function handleAuthenticationLogout(Request $request, Response $response): Response
    {
        // todo create enum for status code or use symfony response
        return $response->withStatus(204);
    }
}
