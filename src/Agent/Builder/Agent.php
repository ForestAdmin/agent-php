<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;


use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Slim\Factory\AppFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class Agent
{
    protected Datasource $compositeDatasource;

    public function __construct(protected array $options)
    {
        $this->compositeDatasource = new Datasource();
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
        $app = AppFactory::create();
        $app->setBasePath('/forest');


        $app->get('/test', function (Request $request, Response $response, $args) {
            $response->getBody()->write("Hello world! this is a test");

            return $response;
        });

        $app->get('/test2', function (Request $request, Response $response, $args) {
            $response->getBody()->write("Hello world! this is a test 2");

            return $response;
        });

        $app->run();

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

//        const router = await httpDriver.getRouter();
//        for (const task of this.mounts) await task(router)
    }
}
