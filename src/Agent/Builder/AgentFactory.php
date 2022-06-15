<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class AgentFactory
{
    protected static Container $container;

    protected Datasource $compositeDatasource;

    //public App $app;

    private ForestAdminHttpDriver $httpDriver;

    public function __construct(protected array $options)
    {
        $this->compositeDatasource = new Datasource();
        $this->httpDriver = new ForestAdminHttpDriver($this->compositeDatasource, $this->options);

    }

    public function addDatasource(DatasourceContract $datasource): self
    {
        // todo add logger
        $datasource->getCollections()->each(
            fn ($collection) => $this->compositeDatasource->addCollection($collection)
        );

        return $this;
    }

    public function getRoutes(): array
    {
        return $this->httpDriver->getRoutes();
    }

//    public function start()
//    {
////        // Check that options are valid
////        const options = OptionsValidator.validate(this.options);
////
////        // Write typings file
////        if (!options.isProduction && options.typingsPath) {
////            const types = TypingGenerator.generateTypes(this.stack.action, options.typingsMaxDepth);
////            await writeFile(options.typingsPath, types, { encoding: 'utf-8' });
////        }
//        $httpDriver = new ForestAdminHttpDriver($this->compositeDatasource, $this->options);
//        $httpDriver->sendSchema();
//
//        $routes = $httpDriver->getRoutes();
//        $this->setRoutes($routes);
////        for (const task of this.mounts) await task(router)
////        dd($this->app);
////        $this->app->get('/', fn () => dd('oksdf'));
//    }

//    /**
//     * Get the globally available instance of the container.
//     *
//     * @return static
//     */
//    public static function getContainer()
//    {
//        return static::$container;
//    }

//    private function initalizeApp(): App
//    {
        // Create Container using PHP-DI
//        $container = new Container();
//        $container->set(
//            'cache',
//            fn () => new FilesystemAdapter(directory: __DIR__. '/../cache')
//        );

//        $app = Bridge::create($container);

        // Allow preflight requests
//        $app->options('/{routes:.+}', function ($request, $response) {
//            return $response;
//        });
//        $app->add(function ($request, $handler) {
//            //'allowedOriginsPatterns' => ['#^.*\.forestadmin\.com\z#u'],
//            $response = $handler->handle($request);
//
//            return $response
//                ->withHeader('Access-Control-Allow-Origin', 'app.development.forestadmin.com')
//                ->withHeader('Access-Control-Allow-Headers', '*')
//                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
//                ->withHeader('Access-Control-Expose-Headers', 'false')
//                ->withHeader('Access-Control-Max-Age', 86400)
//                ->withHeader('Access-Control-Allow-Credentials', 'true');
//        });


//        self::$container = $app->getContainer();

//        return $app;
//    }

}
