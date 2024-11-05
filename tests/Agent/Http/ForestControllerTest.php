<?php

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\AuthenticationOpenIdClient;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\RequireApproval;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\UnprocessableError;
use ForestAdmin\AgentPHP\Agent\Http\ForestController;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

describe('invoke()', function () {
    test('should return a Response', function () {
        $this->buildAgent(new Datasource());
        $_GET['_route'] = 'forest.test';
        $_GET['_route_params'] = [];
        $request = Request::createFromGlobals();

        $forestControllerMock = $this->getMockBuilder(ForestController::class)
            ->onlyMethods(['getClosure'])
            ->getMock();

        $forestControllerMock->expects($this->once())
            ->method('getClosure')
            ->willReturn(
                fn () => [
                    'content' => [
                        'id'    => 1,
                        'title' => 'foo',
                    ],
                ]
            );

        $result = $forestControllerMock->__invoke($request);

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())
            ->toEqual(200)
            ->and(json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR))
            ->toEqual(
                [
                    'id'    => 1,
                    'title' => 'foo',
                ]
            );
    });

    test('should call exceptionHandler', function () {
        $this->buildAgent(new Datasource());
        $_GET['_route'] = 'forest.test';
        $_GET['_route_params'] = [];
        $request = Request::createFromGlobals();

        $forestControllerMock = $this->getMockBuilder(ForestController::class)
            ->onlyMethods(['getClosure'])
            ->getMock();
        $forestControllerMock->expects($this->once())
            ->method('getClosure')
            ->willReturn(fn () => throw new \Exception());

        $result = $forestControllerMock->__invoke($request);

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())
            ->toEqual(500)
            ->and(json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR))
            ->toEqual(
                [
                    'errors' => [
                        [
                            'name'   => 'Exception',
                            'detail' => 'Unexpected error',
                            'status' => 500,
                        ],
                    ],
                ]
            );
    });

    test('should call exceptionHandler on custom HttpException error', function () {
        $this->buildAgent(new Datasource());
        $_GET['_route'] = 'forest.test';
        $_GET['_route_params'] = [];
        $request = Request::createFromGlobals();

        $forestControllerMock = $this->getMockBuilder(ForestController::class)
            ->onlyMethods(['getClosure'])
            ->getMock();
        $forestControllerMock->expects($this->once())
            ->method('getClosure')
            ->willReturn(fn () => throw new UnprocessableError(422, []));

        $result = $forestControllerMock->__invoke($request);

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())
            ->toEqual(422)
            ->and(json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR))
            ->toEqual(
                [
                    'errors' => [
                        [
                            'name'   => 'UnprocessableError',
                            'detail' => 422,
                            'status' => 422,
                        ],
                    ],
                ]
            );
    });

    test('should call exceptionHandler on custom RequireApproval -> HttpException error', function () {
        $this->buildAgent(new Datasource());
        $_GET['_route'] = 'forest.test';
        $_GET['_route_params'] = [];
        $request = Request::createFromGlobals();

        $forestControllerMock = $this->getMockBuilder(ForestController::class)
            ->onlyMethods(['getClosure'])
            ->getMock();
        $forestControllerMock->expects($this->once())
            ->method('getClosure')
            ->willReturn(fn () => throw new RequireApproval('This action requires to be approved.', [], 'CustomActionRequiresApprovalError', ['foo' => 'bar']));

        $result = $forestControllerMock->__invoke($request);

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())
            ->toEqual(403)
            ->and(json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR))
            ->toEqual(
                [
                    'errors' => [
                        [
                            'name'   => 'CustomActionRequiresApprovalError',
                            'detail' => 'This action requires to be approved.',
                            'status' => 403,
                            'data'   => ['foo' => 'bar'],
                        ],
                    ],
                ]
            );
    });

    test('should call exceptionHandler on custom AuthenticationOpenIdClient -> HttpException error', function () {
        $this->buildAgent(new Datasource());
        $_GET['_route'] = 'forest.test';
        $_GET['_route_params'] = [];
        $request = Request::createFromGlobals();

        $forestControllerMock = $this->getMockBuilder(ForestController::class)
            ->onlyMethods(['getClosure'])
            ->getMock();
        $forestControllerMock->expects($this->once())
            ->method('getClosure')
            ->willReturn(fn () => throw new AuthenticationOpenIdClient('TrialBlockedError', 'Your free trial has ended. We hope you enjoyed your experience with Forest Admin.', '{"renderingId":1}'));

        $result = $forestControllerMock->__invoke($request);

        expect($result)
            ->toBeInstanceOf(JsonResponse::class)
            ->and($result->getStatusCode())
            ->toEqual(401)
            ->and(json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR))
            ->toEqual(
                [
                    'error'             => 'TrialBlockedError',
                    'error_description' => 'Your free trial has ended. We hope you enjoyed your experience with Forest Admin.',
                    'state'             => '{"renderingId":1}',
                ]
            );
    });
});

describe('response()', function () {
    test('with the content-type "text/csv" should return a Response', function () {
        $this->buildAgent(new Datasource());

        $_GET['_route'] = 'forest';
        $_GET['_route_params'] = [];

        $forestController = new ForestController();
        $data = [
            'content' => 'foo',
            'headers' => [
                'Content-type' => 'text/csv',
            ],
        ];

        expect($this->invokeMethod($forestController, 'response', [$data]))
            ->toBeInstanceOf(Response::class);
    });

    test('should return a JsonResponse', function () {
        $this->buildAgent(new Datasource());

        $_GET['_route'] = 'forest';
        $_GET['_route_params'] = [];

        $forestController = new ForestController();
        $data = ['content' => 'foo'];

        expect($this->invokeMethod($forestController, 'response', [$data]))
            ->toBeInstanceOf(JsonResponse::class);
    });

    test('of an action should return a JsonResponse', function () {
        $this->buildAgent(new Datasource());

        $_GET['_route'] = 'forest';
        $_GET['_route_params'] = [];

        $forestController = new ForestController();
        $data = [
            'headers'   => [],
            'is_action' => true,
            'type'      => 'Success',
            'success'   => 'Success',
            'refresh'   => ['relationships' => []],
            'html'      => null,
        ];

        expect($this->invokeMethod($forestController, 'response', [$data]))
            ->toBeInstanceOf(JsonResponse::class);
    });

    test('of an action (file) should return a Response', function () {
        $this->buildAgent(new Datasource());

        $_GET['_route'] = 'forest';
        $_GET['_route_params'] = [];

        $forestController = new ForestController();
        $data = [
            'headers'   => [],
            'is_action' => true,
            'type'      => 'File',
            'name'      => 'filedemo',
            'mimeType'  => 'text/plain',
            'stream'    => __DIR__ . '/Files/example-file.txt',
        ];

        expect($this->invokeMethod($forestController, 'response', [$data]))
            ->toBeInstanceOf(BinaryFileResponse::class);
    });
});
