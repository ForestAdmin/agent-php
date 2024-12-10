<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent;

use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\ClassFinder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

use function ForestAdmin\config;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @codeCoverageIgnore
 */
class EloquentDatasource extends BaseDatasource
{
    private array $models = [];

    private array $dbConnections = [];

    /**
     * @throws \ReflectionException
     */
    public function __construct(
        array $databaseConfig,
        string $name = 'eloquent_collection',
        protected bool $supportPolymorphicRelations = false,
        ?array $liveQueryConnections = null
    ) {
        parent::__construct($databaseConfig);
        $this->name = $name;

        if (is_string($liveQueryConnections)) {
            $this->liveQueryConnections = [$liveQueryConnections => \config('database.default')];
        } elseif (is_array($liveQueryConnections)) {
            $this->liveQueryConnections = $liveQueryConnections;
        }

        $this->generate();
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function generate(): void
    {
        $finder = new ClassFinder(config('projectDir'));
        $this->models = $finder->getModelsInNamespace('App');
        $dbConnections = [];
        foreach ($this->models as $model) {
            $modelInstance = new $model();
            $dbConnections[] = $modelInstance->getConnection()->getName();

            try {
                $this->addCollection(new EloquentCollection($this, $modelInstance, $this->supportPolymorphicRelations));
            } catch (\Exception $e) {
                // do nothing
            }
        }

        $this->dbConnections = array_unique($dbConnections);
    }

    public function addCollection(CollectionContract $collection): void
    {
        if (! $this->collections->has($collection->getName())) {
            $this->collections->put($collection->getName(), $collection);
        }
    }

    public function findModelByTableName(string $tableName): Model|bool
    {
        foreach ($this->models as $class) {
            /** @var Model $model */
            $model = new $class();
            if ($model->getTable() === $tableName || $model->getTable() === Str::pluralStudly($tableName)) {
                return $model;
            }
        }

        return false;
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getDbConnections(): array
    {
        return $this->dbConnections;
    }

    public function getLiveQueryConnections(): ?array
    {
        return $this->liveQueryConnections;
    }

    public function executeNativeQuery(string $connectionName, string $query, array $bind = []): array
    {
        if (! isset($this->liveQueryConnections[$connectionName])) {
            throw new ForestException("Native query connection '{$connectionName}' is unknown.", 422);
        }

        $connection = \config('database.connections.' . $this->liveQueryConnections[$connectionName]);
        $orm = new Manager();
        $orm->addConnection($connection);

        return $orm->getDatabaseManager()->select($query, $bind);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __serialize(): array
    {
        return array_merge(
            parent::__serialize(),
            [
                'supportPolymorphicRelations'   => $this->supportPolymorphicRelations,
                'liveQueryConnections'          => $this->liveQueryConnections,
            ]
        );
    }

    /**
     * @codeCoverageIgnore
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $finder = new ClassFinder(config('projectDir'));
        $this->models = $finder->getModelsInNamespace('App');
        $this->supportPolymorphicRelations = $data['supportPolymorphicRelations'];
        $this->liveQueryConnections = $data['liveQueryConnections'];
    }
}
