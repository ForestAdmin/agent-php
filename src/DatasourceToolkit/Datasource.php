<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use Composer\Autoload\ClassMapGenerator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\Chart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\DataSourceSchema;
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

        return $collection ?? throw new \Exception("Collection $name not found.");
    }

    public function addCollection(CollectionContract $collection): void
    {
        if ($this->collections->first(fn ($item) => $item->getName() === $collection->getName())) {
            throw new \Exception('Collection ' . $collection->getName() . ' already defined in datasource');
        }
        $this->collections->push($collection);
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

/*
 *  protected _collections: { [collectionName: string]: T } = {};

  get collections(): T[] {
    return Object.values(this._collections);
  }

  get schema(): DataSourceSchema {
    return { charts: [] };
  }

  getCollection(name: string): T {
    const collection = this._collections[name];

    if (collection === undefined) throw new Error(`Collection '${name}' not found.`);

    return collection;
  }

  public addCollection(collection: T): void {
    if (this._collections[collection.name] !== undefined)
      throw new Error(`Collection '${collection.name}' already defined in datasource`);

    this._collections[collection.name] = collection;
  }

  renderChart(caller: Caller, name: string): Promise<Chart> {
    throw new Error(`No chart named '${name}' exists on this datasource.`);
  }
 */
