<?php

namespace ForestAdmin\AgentPHP\DatasourceEloquent;

use ForestAdmin\AgentPHP\BaseDatasource\BaseDatasource;
use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\ClassFinder;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;

use function ForestAdmin\config;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @codeCoverageIgnore
 */
class EloquentDatasource extends BaseDatasource
{
    private array $models = [];

    /**
     * @throws \ReflectionException
     */
    public function __construct(array $databaseConfig)
    {
        parent::__construct($databaseConfig);
        $this->generate();
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function generate(): void
    {
        $finder = new ClassFinder(config('projectDir'));
        $this->models = $finder->getModelsInNamespace();
        foreach ($this->models as $model) {
            $this->addCollection(new EloquentCollection($this, new $model()));
        }
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
}
