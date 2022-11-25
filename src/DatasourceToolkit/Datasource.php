<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use Composer\Autoload\ClassMapGenerator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\DataSourceSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as IlluminateCollection;

class Datasource implements DatasourceContract
{
    protected IlluminateCollection $collections;

    protected DataSourceSchema $schema;

    public function __construct()
    {
        $this->schema = new DataSourceSchema();
        $this->collections = new IlluminateCollection();
    }

    public function getCollections(): IlluminateCollection
    {
        return $this->collections;
    }

    public function getSchema(): DataSourceSchema
    {
        return $this->schema;
    }

    public function getCollection(string $name): CollectionContract
    {
        $collection = $this->collections->first(fn ($item) => $item->getName() === $name);

        return $collection ?? throw new ForestException("Collection $name not found.");
    }

    public function getCollectionByClassName(string $name): CollectionContract
    {
        $collection = $this->collections->first(fn ($item) => $item->getClassName() === $name);

        return $collection ?? throw new ForestException("Collection $name not found.");
    }

    /**
     * @throws ForestException
     */
    public function addCollection(CollectionContract $collection): void
    {
        if ($this->collections->has($collection->getName())) {
            throw new ForestException('Collection ' . $collection->getName() . ' already defined in datasource');
        }
        $this->collections->put($collection->getName(), $collection);
    }

    public function renderChart(Caller $caller, string $name): Chart
    {
        // TODO: Implement renderChart() method.
    }

    /**
     * Fetch all files in the model directory
     *
     * @param string $directory
     * @return Collection
     */
    private function fetchFiles(string $directory): Collection
    {
        $files = new Collection();

        foreach (glob($directory, GLOB_ONLYDIR) as $dir) {
            if (file_exists($dir)) {
                $fileClass = ClassMapGenerator::createMap($dir);
                foreach (array_keys($fileClass) as $file) {
                    $files->push($file);
                }
            }
        }

        return $files;
    }
}
