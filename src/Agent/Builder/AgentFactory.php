<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use DI\Container;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class AgentFactory
{
    private const TTL_CONFIG = 3600;

    private const TTL_SCHEMA = 7200;

    protected static Container $container;

    protected DatasourceCustomizer $customizer;

    public function __construct(array $config, array $services = [])
    {
        $this->customizer = new DatasourceCustomizer();
        $this->buildContainer($services);
        $this->buildCache($config);
    }

    public function addDatasource(Datasource $datasource, array $options = []): self
    {
        $this->customizer->addDatasource($datasource, $options);

        return $this;
    }

    public function build(): void
    {
        self::$container->set('datasource', $this->customizer->getStack()->dataSource);

        $this->sendSchema($this->customizer->getStack()->dataSource);
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
        $this->customizer->customizeCollection($name, $handle);

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

    /**
     * @throws \ErrorException
     * @throws \JsonException
     */
    public function sendSchema(): void
    {
        $schema = SchemaEmitter::getSerializedSchema($this->customizer->getStack()->dataSource);

        $schemaIsKnown = false;
        if (Cache::get('schemaFileHash') === $schema['meta']['schemaFileHash']) {
            $schemaIsKnown = true;
        }

        if (! $schemaIsKnown) {
            // TODO this.options.logger('Info', 'Schema was updated, sending new version');
            ForestHttpApi::uploadSchema($schema);
            Cache::put('schemaFileHash', $schema['meta']['schemaFileHash'], self::TTL_SCHEMA);
        } else {
            // TODO this.options.logger('Info', 'Schema was not updated since last run');
        }
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
        $directory = $config['cacheDir'];
        self::$container->set('cache', new CacheServices($filesystem, $directory));
        self::$container->get('cache')->add('config', $config, self::TTL_CONFIG);
    }
}
