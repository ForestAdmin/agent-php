<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use DI\Container;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Illuminate\Support\Collection as IlluminateCollection;

class AgentFactory
{
    private const TTL = 3600;

    protected static Container $container;

    protected Datasource $compositeDatasource;

    protected DecoratorsStack $stack;

    protected IlluminateCollection $customizations;

    public function __construct(array $config, array $services)
    {
        $this->compositeDatasource = new Datasource();
        $this->stack = new DecoratorsStack($this->compositeDatasource);
        $this->customizations = new IlluminateCollection();
        $this->buildContainer($services);
        $this->buildCache($config);
    }

    public function addDatasource(Datasource $datasource): self
    {
        $datasource->getCollections()->each(
            fn ($collection) => $this->compositeDatasource->addCollection($collection)
        );
//        self::$container->set('datasource', $this->compositeDatasource);

        $this->stack->build();

        return $this;
    }

    public function build(): void
    {
        self::$container->set('datasource', $this->stack->dataSource);
    }

    /**
     * Allow to interact with a decorated collection
     * @example
     * .customizeCollection('books', books => books.renameField('xx', 'yy'))
     * @param string   $name the name of the collection to manipulate
     * @param \Closure $handle a function that provide a
     *   collection builder on the given collection name
     * @return $this
     */
    public function customizeCollection(string $name, \Closure $handle): self
    {
        if ($this->stack->dataSource->getCollection($name)) {
            $handle(new CollectionBuilder($this->stack, $name));
        }

        return $this;
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
