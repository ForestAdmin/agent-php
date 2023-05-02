<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins;

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;

abstract class Plugin
{
    public function __construct(DatasourceCustomizer $datasourceCustomizer, CollectionCustomizer $collectionCustomizer, $options)
    {
    }
}
