<?php

namespace ForestAdmin\AgentPHP\DatasourceToolkit;

use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\CollectionContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\DataSourceSchema;
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
        // TODO: Implement getCollection() method.
    }

    public function addCollection(CollectionContract $collection): void
    {
        $this->collections->push($collection);
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
