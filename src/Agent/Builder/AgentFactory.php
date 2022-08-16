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

use function ForestAdmin\cacheRemember;
use function ForestAdmin\config;
use function ForestAdmin\forget;

use Ramsey\Uuid\Uuid;

class AgentFactory
{
    private const TTL = 3600;

    protected static Container $container;

    protected Datasource $compositeDatasource;

    public function __construct(protected array $options)
    {
        $this->compositeDatasource = new Datasource();
        $this->buildContainer();
    }

    public function addDatasources(array $datasources): void
    {
        if (! config('isProduction')) {
            forget('datasource');
        }

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

    public function renderChart(Chart $chart): array
    {
        return JsonApi::renderItem(
            [
                'id'    => Uuid::uuid4(),
                'value' => $chart->serialize(),
            ],
            'stats',
            new BasicArrayTransformer()
        );
    }

    public static function getContainer(): Container
    {
        return static::$container;
    }

    public static function get(string $key)
    {
        return self::$container->get($key);
    }

    private function buildContainer(): void
    {
        self::$container = new Container();

        //--- set Cache  ---//
        $filesystem = new Filesystem();
        $directory = $this->options['projectDir'] . '/forest-cache' ;
        self::$container->set('cache', new CacheServices($filesystem, $directory));
        // maybe move config into container ??
        self::$container->get('cache')->add('config', $this->options, self::TTL);
    }
}
