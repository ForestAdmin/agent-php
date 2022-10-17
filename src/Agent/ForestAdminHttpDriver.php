<?php

namespace ForestAdmin\AgentPHP\Agent;

use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class ForestAdminHttpDriver
{
    public static function sendSchema(Datasource $datasource): void
    {
        $schema = SchemaEmitter::getSerializedSchema($datasource);
        $schemaIsKnown = ForestHttpApi::hasSchema($schema['meta']['schemaFileHash']);

        if (! $schemaIsKnown) {
            // TODO this.options.logger('Info', 'Schema was updated, sending new version');
            ForestHttpApi::uploadSchema($schema);
        } else {
            // TODO this.options.logger('Info', 'Schema was not updated since last run');
        }
    }
}
