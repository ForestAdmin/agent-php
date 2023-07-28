<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use Closure;
use DI\Container;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Services\LoggerServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

use function ForestAdmin\config;

class AgentFactory
{
    protected const TTL_CONFIG = 3600;

    protected const TTL_SCHEMA = 7200;

    protected DatasourceCustomizer $customizer;

    protected bool $hasEnvSecret;

    protected static Container $container;

    public function __construct(array $config)
    {
        $this->hasEnvSecret = isset($config['envSecret']);
        $this->customizer = new DatasourceCustomizer();
        $this->buildContainer();
        $this->buildCache($config);
        $this->buildLogger($config);
    }

    public function addDatasource(Datasource $datasource, array $options = []): self
    {
        $this->customizer->addDatasource($datasource, $options);

        return $this;
    }

    public function addChart(string $name, Closure $definition): self
    {
        $this->customizer->addChart($name, $definition);

        return $this;
    }

    public function use(string $plugin, array $options = []): self
    {
        $this->customizer->use($plugin, $options);

        return $this;
    }

    public function build(): void
    {
        self::$container->set('datasource', $this->customizer->getStack()->dataSource);

        self::sendSchema();
    }

    /**
     * Allow to interact with a decorated collection
     * @example
     * ->customizeCollection('books', books => books.renameField('xx', 'yy'))
     * @param string   $name the name of the collection to manipulate
     * @param Closure $handle a function that provide a
     *   collection builder on the given collection name
     * @return $this
     */
    public function customizeCollection(string $name, Closure $handle): self
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
     * @throws \JsonException
     * @codeCoverageIgnore
     */
    public static function sendSchema(bool $force = false): void
    {
        if (config('envSecret')) {
            $schema = SchemaEmitter::getSerializedSchema(self::get('datasource'));

            $schemaIsKnown = false;
            if (Cache::get('schemaFileHash') === $schema['meta']['schemaFileHash']) {
                $schemaIsKnown = true;
            }

            if (! $schemaIsKnown || $force) {
                Logger::log('Info', 'schema was updated, sending new version');
                ForestHttpApi::uploadSchema($schema);
                Cache::put('schemaFileHash', $schema['meta']['schemaFileHash'], self::TTL_SCHEMA);
            } else {
                Logger::log('Info', 'Schema was not updated since last run');
            }
        }
    }

    private function buildContainer(): void
    {
        self::$container = new Container();
    }

    private function buildCache(array $config): void
    {
        $filesystem = new Filesystem();
        $directory = $config['cacheDir'];
        self::$container->set('cache', new CacheServices($filesystem, $directory));

        if ($this->hasEnvSecret) {
            self::$container->get('cache')->add('config', $config, self::TTL_CONFIG);
        }
    }

    private function buildLogger(array $config): void
    {
        $logger = new LoggerServices(
            loggerLevel: $config['loggerLevel'] ?? 'Info',
            logger: $config['logger'] ?? null
        );

        self::$container->set('logger', $logger);
    }
}
