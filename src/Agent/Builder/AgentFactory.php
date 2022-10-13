<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use DI\Container;
use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

use function ForestAdmin\config;

use Ramsey\Uuid\Uuid;

class AgentFactory
{
    private const TTL = 3600;

    protected static Container $container;

    protected Datasource $compositeDatasource;

    public function __construct(array $config, array $services)
    {
        $this->compositeDatasource = new Datasource();
        $this->buildContainer($services);
        $this->buildCache($config);
    }

    public function addDatasource(Datasource $datasource): void
    {
        $datasource->getCollections()->each(
            fn ($collection) => $this->compositeDatasource->addCollection($collection)
        );

        self::$container->set('datasource', $this->compositeDatasource);
    }

    public function addDatasources(array $datasources): void
    {
        if (! self::$container->has('datasource') || ! config('isProduction')) {
            foreach ($datasources as $datasource) {
                if (! $datasource instanceof DatasourceContract) {
                    throw new \Exception('Invalid datasource');
                }
                // todo add logger
                $datasource->getCollections()->each(
                    fn ($collection) => $this->compositeDatasource->addCollection($collection)
                );
            }
            self::$container->set('datasource', $this->compositeDatasource);
        }
    }

    public static function getContainer(): ?Container
    {
        return static::$container ?? null;
    }

    public static function get(string $key)
    {
        return self::$container->get($key);
    }

    private function buildContainer(array $services): void
    {
        self::$container = new Container();
        foreach ($services as $key => $value) {
            self::$container->set($key, $value);
        }
    }

    private function buildCache(array $config): void
    {
        $filesystem = new Filesystem();
        $directory = $config['projectDir'] . '/forest-cache' ;
        self::$container->set('cache', new CacheServices($filesystem, $directory));
        self::$container->get('cache')->add('config', $config, self::TTL);
    }
}
