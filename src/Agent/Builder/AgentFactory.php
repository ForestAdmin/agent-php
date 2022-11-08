<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use DI\Container;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
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
        self::$container->get('cache')->add('config', $config, $config['permissionsCacheDurationInSeconds']);
    }

    private function loadOptions(array $options): array
    {
        if (! isset($options['authSecret'], $options['envSecret'], $options['isProduction'])) {
            throw new ForestException('the keys authSecret, envSecret, isProduction are mandatory.');
        }

        return [
            'authSecret'                        => $options['authSecret'],
            'envSecret'                         => $options['envSecret'],
            'isProduction'                      => $options['isProduction'],
            'appUrl'                            => $options['appUrl'],
            'customizeErrorMessage'             => $options['customizeErrorMessage'] ?? null,
            'forestServerUrl'                   => $options['customizeErrorMessage'] ?? 'https://api.forestadmin.com',
            'logger'                            => $options['customizeErrorMessage'] ?? null,
            'loggerLevel'                       => $options['customizeErrorMessage'] ?? 'Info',
            'permissionsCacheDurationInSeconds' => $options['customizeErrorMessage'] ?? 15 * 60,
            'prefix'                            => $options['prefix'] ?? '', // todo fix because prefix is empty by default => prefix is before forest
            'schemaPath'                        => $options['prefix'] ?? '.forestadmin-schema.json',
            'projectDir'                        => $options['projectDir'],
            'debug'                             => $options['debug'],
        ];
    }
}
