<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use DI\Container;
use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Http\Router;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Support\Collection;

class AgentFactory
{
    private const TTL = 3600;

    protected static Collection $container;

    protected Datasource $compositeDatasource;

    public function __construct(protected array $options)
    {
        $this->compositeDatasource = new Datasource();
        $this->buildContainer();
    }

    public function addDatasource(DatasourceContract $datasource): self
    {
        // todo add logger
        $datasource->getCollections()->each(
            fn ($collection) => $this->compositeDatasource->addCollection($collection)
        );

//        forest_cache('httpDriver',  new ForestAdminHttpDriver($this->compositeDatasource));

        return $this;
    }

//    public function getRoutes(): array
//    {
//        $services = new ForestAdminHttpDriverServices();
//        $router = new Router(self::$container->get('cache')->get('httpDriver'), $services);
//
//        return $router->makeRoutes();
//    }

    public static function getContainer(): Collection
    {
        return static::$container;
    }

    private function buildContainer(): void
    {
        self::$container = new Collection();

        //--- set Cache  ---//
        $filesystem = new Filesystem();
        $directory = $this->options['projectDir'] . '/forest-cache' ;
        self::$container->getOrPut('cache', fn () => new CacheServices($filesystem, $directory));
        self::$container->get('cache')->add('config', $this->options, self::TTL);

        //--- set HttpDriver  ---//
        self::$container->get('cache')->add(
            'httpDriver',
            fn () => new ForestAdminHttpDriver($this->compositeDatasource),
            self::TTL
        );
    }
}
