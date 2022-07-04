<?php

namespace ForestAdmin\AgentPHP\Agent;

use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class ForestAdminHttpDriver
{
    public function __construct(
        protected Datasource $dataSource, //todo maybe not necessary here & we could remove the class to the cache
    ) {
    }

    public function sendSchema(Datasource $datasource): void
    {
        $schema = SchemaEmitter::getSerializedSchema($datasource);
        dd($schema);
//        $schemaIsKnown = ForestHttpApi::hasSchema($this->options, $schema['meta']['schemaFileHash']);
        $schemaIsKnown = false;
        if (! $schemaIsKnown) {
            // TODO this.options.logger('Info', 'Schema was updated, sending new version');
            ForestHttpApi::uploadSchema($schema);
        } else {
            // TODO this.options.logger('Info', 'Schema was not updated since last run');
        }
    }
}
