<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use DI\Bridge\Slim\Bridge;
use DI\Container;
use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Agent
{
    protected static Container $container;

    protected Datasource $compositeDatasource;

    public App $app;

    public function __construct(protected array $options)
    {
        $this->compositeDatasource = new Datasource();
        $this->app = $this->initalizeApp();
    }

    public function addDatasource(DatasourceContract $datasource): self
    {
        // todo add logger
        $datasource->getCollections()->each(
            fn ($collection) => $this->compositeDatasource->addCollection($collection)
        );

        return $this;
    }

    public function mountOnStandaloneServer(): self
    {
//        $this->app->get('/test', function (Request $request, Response $response) {
//            $response->getBody()->write("Hello world! this is a test");
//
//            return $response;
//        });
//
//        $this->app->get('/test2', function (Request $request, Response $response) {
//            $response->getBody()->write("Hello world! this is a test 2");
//
//            return $response;
//        });
//        dd($this->app);
        return $this;
    }

    public function start()
    {
//        // Check that options are valid
//        const options = OptionsValidator.validate(this.options);
//
//        // Write typings file
//        if (!options.isProduction && options.typingsPath) {
//            const types = TypingGenerator.generateTypes(this.stack.action, options.typingsMaxDepth);
//            await writeFile(options.typingsPath, types, { encoding: 'utf-8' });
//        }
//        dd('ok');
        $httpDriver = new ForestAdminHttpDriver($this->compositeDatasource, $this->options);
        $httpDriver->sendSchema();

        $routes = $httpDriver->getRoutes();
        $this->setRoutes($routes);
//        for (const task of this.mounts) await task(router)
//        dd($this->app);
//        $this->app->get('/', fn () => dd('oksdf'));
        $this->app->run();
    }

    private function initalizeApp(): App
    {
        // Create Container using PHP-DI
        $container = new Container();
        $container->set(
            'cache',
            fn () => new FilesystemAdapter(directory: __DIR__. '/../cache')
        );

        $app = Bridge::create($container);
        $app->setBasePath('/forest');

        self::$container = $app->getContainer();

        return $app;
    }

    private function setRoutes(array $routes): void
    {
        foreach ($routes as $route) {
            $route->setupRoutes($this->app->getRouteCollector());
        }
//        dd($this->app);
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getContainer()
    {
        return static::$container;
    }
}
