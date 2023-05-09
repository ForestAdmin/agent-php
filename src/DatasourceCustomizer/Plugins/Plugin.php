<?php

namespace ForestAdmin\AgentPHP\DatasourceCustomizer\Plugins;

use ForestAdmin\AgentPHP\DatasourceCustomizer\CollectionCustomizer;
use ForestAdmin\AgentPHP\DatasourceCustomizer\DatasourceCustomizer;

interface Plugin
{
    public function run(DatasourceCustomizer $datasourceCustomizer, ?CollectionCustomizer $collectionCustomizer = null, $options = []): void;
}
