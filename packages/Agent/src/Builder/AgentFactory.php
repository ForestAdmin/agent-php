<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use Closure;
use ForestAdmin\AgentPHP\Agent\Facades\Cache;
use ForestAdmin\AgentPHP\Agent\Facades\Logger;
use ForestAdmin\AgentPHP\Agent\Services\LoggerServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

use function ForestAdmin\config;

use Laravel\SerializableClosure\SerializableClosure;

class AgentFactory
{
    public const TTL_CONFIG = 3600;

    public const TTL = 60;

    public const TTL_SCHEMA = 60;

    protected DatasourceCustomizer $customizer;

    protected bool $hasEnvSecret;

    public static ?array $fileCacheOptions;

    protected bool $isBuild = false;

    protected ?DatasourceContract $computedDatasource = null;

    public function __construct(protected array $config)
    {
        $this->hasEnvSecret = isset($config['envSecret']);
        $this->customizer = new DatasourceCustomizer();
        $this->buildCache();
        $this->buildLogger();
    }

    public function createAgent(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        $this->buildLogger();
        if ($this->hasEnvSecret) {
            if (isset($this->config['customizeErrorMessage']) && is_callable($this->config['customizeErrorMessage']) && ! is_string($this->config['customizeErrorMessage'])) {
                Cache::put('customizeErrorMessage', new SerializableClosure($this->config['customizeErrorMessage']));
            }

            Cache::put('config', $this->serializeConfig(), self::TTL_CONFIG);
        }

        Cache::put('forestAgent', new SerializableClosure(fn () => $this), self::TTL);

        return $this;
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
        if (! $this->isBuild) {
            $this->computedDatasource = $this->customizer->getDatasource();
            Cache::put('forestAgent', new SerializableClosure(fn () => $this), self::TTL);
            self::sendSchema();
            $this->isBuild = true;
        }
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

    public function removeCollection(string|array $names): self
    {
        $this->customizer->removeCollection($names);

        return $this;
    }

    public static function get(string $key)
    {
        if ($key === 'datasource') {
            return self::getDatasource();
        }

        return Cache::get($key);
    }

    public static function getFileCacheOptions(): ?array
    {
        return self::$fileCacheOptions ?? null;
    }

    public static function getDatasource()
    {
        /** @var self $instance */
        $forestAgentClosure = Cache::get('forestAgent');

        return $forestAgentClosure()->getDatasourceInstance();
    }

    public static function getInstance()
    {
        $forestAgentClosure = Cache::get('forestAgent');

        return $forestAgentClosure();
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

    private function serializeConfig(): array
    {
        $serializableConfig = $this->config;
        unset($serializableConfig['logger'], $serializableConfig['customizeErrorMessage']);

        return $serializableConfig;
    }

    private function buildCache(): void
    {
        if ($this->hasEnvSecret) {
            if(! Cache::apcuEnabled()) {
                $filesystem = new Filesystem();
                $directory = $this->config['cacheDir'];
                $disabledApcuCache = $this->config['disabledApcuCache'] ?? false;
                self::$fileCacheOptions = compact('filesystem', 'directory', 'disabledApcuCache');
            }

            Cache::add('config', $this->serializeConfig(), self::TTL_CONFIG);
        }
    }

    private function buildLogger(): void
    {
        $logger = new LoggerServices(
            loggerLevel: $this->config['loggerLevel'] ?? 'Info',
            logger: $this->config['logger'] ?? null
        );

        Cache::put('logger', new SerializableClosure(fn () => $logger));
    }

    public function getDatasourceInstance(): ?DatasourceContract
    {
        return $this->computedDatasource;
    }

    /**
     * @codeCoverageIgnore
    */
    public function __serialize(): array
    {
        return [
            'hasEnvSecret'       => $this->hasEnvSecret,
            'isBuild'            => $this->isBuild,
            'computedDatasource' => $this->computedDatasource,
            'customizer'         => $this->customizer,
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function __unserialize(array $data): void
    {
        $this->hasEnvSecret = $data['hasEnvSecret'];
        $this->isBuild = $data['isBuild'];
        $this->computedDatasource = $data['computedDatasource'];
        $this->config = Cache::get('config');
        $this->customizer = $data['customizer'];
    }

    public function getCustomizer(): DatasourceCustomizer
    {
        return $this->customizer;
    }
}
