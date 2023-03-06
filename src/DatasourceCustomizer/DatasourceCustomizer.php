<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer;

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DecoratorsStack;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\PublicationCollection\PublicationCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\RenameCollection\RenameCollectionDatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Contracts\DatasourceContract;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class DatasourceCustomizer
{
    protected Datasource $compositeDatasource;

    protected DecoratorsStack $stack;

    public function __construct()
    {
        $this->compositeDatasource = new Datasource();
        $this->stack = new DecoratorsStack($this->compositeDatasource);
    }

    public function addDatasource(DatasourceContract $datasource, array $options = []): self
    {
        if (isset($options['include']) || isset($options['exclude'])) {
            $datasource = new PublicationCollectionDatasourceDecorator($datasource);
            $datasource->build();
            $datasource->keepCollectionsMatching($options['include'] ?? [], $options['exclude'] ?? []);
        }

        if (isset($options['rename'])) {
            $datasource = new RenameCollectionDatasourceDecorator($datasource);
            $datasource->build();
            $datasource->renameCollections($options['rename'] ?? []);
        }

        $datasource->getCollections()->each(
            fn ($collection) => $this->compositeDatasource->addCollection($collection)
        );

        $this->stack->build();

        return $this;
    }

    /**
     * Allow to interact with a decorated collection
     * @param string   $name the name of the collection to manipulate
     * @param \Closure $handle a function that provide a collection builder on the given collection name
     * @return $this
     */
    public function customizeCollection(string $name, \Closure $handle): self
    {
        if ($this->stack->dataSource->getCollection($name)) {
            $handle(new CollectionCustomizer($this->stack, $name));
        }

        return $this;
    }

    /**
     * @return DecoratorsStack
     */
    public function getStack(): DecoratorsStack
    {
        return $this->stack;
    }
}