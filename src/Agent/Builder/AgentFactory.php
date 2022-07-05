<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Support\Collection;
use function ForestAdmin\cache;

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
        //$mainDatasource = cache('datasource');
        $mainDatasource = new Datasource();

        // todo add logger
        $datasource->getCollections()->each(
            fn ($collection) => $mainDatasource->addCollection($collection)
        );

        cache('datasource', $mainDatasource);

        return $this;
    }

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

        self::$container->get('cache')->add(
            'datasource',
            fn () => new Datasource(),
            self::TTL
        );
    }
}