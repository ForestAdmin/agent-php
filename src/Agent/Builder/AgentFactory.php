<?php

namespace ForestAdmin\AgentPHP\Agent\Builder;

use ForestAdmin\AgentPHP\Agent\Facades\JsonApi;
use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BasicArrayTransformer;
use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Agent\Utils\Filesystem;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use Ramsey\Uuid\Uuid;
use function ForestAdmin\cacheRemember;
use function ForestAdmin\config;
use function ForestAdmin\forget;
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

    public function addDatasources(array $datasources): void
    {
        if (! config('isProduction')) {
            forget('datasource');
        }

        cacheRemember(
            'datasource',
            static function () use ($datasources) {
                $mainDatasource = new Datasource();
                /** @var DatasourceContract $datasource */
                foreach ($datasources as $datasource) {
                    if (! $datasource instanceof DatasourceContract) {
                        throw new \Exception('Invalid datasource');
                    }
                    // todo add logger
                    $datasource->getCollections()->each(
                        fn ($collection) => $mainDatasource->addCollection($collection)
                    );
                }

                return $mainDatasource;
            }
        );
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
    }
}
