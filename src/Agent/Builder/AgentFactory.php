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
    private const CONFIG_TTL = 3600;

    protected static Collection $container;

    protected Datasource $compositeDatasource;

    private ForestAdminHttpDriver $httpDriver;

    public function __construct(protected array $options)
    {
        $this->compositeDatasource = new Datasource();
        $this->httpDriver = new ForestAdminHttpDriver($this->compositeDatasource, $this->options);
        $this->buildContainer();
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
        $services = new ForestAdminHttpDriverServices($this->options);
        $router = new Router($this->httpDriver, $this->options, $services);

        return $router->makeRoutes();
    }

    public static function getContainer(): Collection
    {
        return static::$container;
    }

    private function buildContainer(): void
    {
        self::$container = new Collection();

        $filesystem = new Filesystem();
        $directory = $this->options['projectDir'] . '/forest-cache' ;

        self::$container->put('cache', new CacheServices($filesystem, $directory));
        self::$container->get('cache')->put('config', $this->options, self::CONFIG_TTL);
    }
}
