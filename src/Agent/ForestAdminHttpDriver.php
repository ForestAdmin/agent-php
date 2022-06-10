<?php

namespace ForestAdmin\AgentPHP\Agent;

use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\Agent\Utils\ForestHttpApi;
use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

class ForestAdminHttpDriver
{
    public function __construct(
        protected Datasource $dataSource,
        protected array $options
    ) {
    }

    public function getRoutes(): array
    {
        $services = new ForestAdminHttpDriverServices($this->options);
        $routes = (new Router($this->dataSource, $this->options, $services))->makeRoutes();

        return $routes;
    }

    public function sendSchema(): void
    {
        $schema = SchemaEmitter::getSerializedSchema($this->options, $this->dataSource);
        $schemaIsKnown = ForestHttpApi::hasSchema($this->options, $schema['meta']['schemaFileHash']);

        if (! $schemaIsKnown) {
            // TODO this.options.logger('Info', 'Schema was updated, sending new version');
            ForestHttpApi::uploadSchema($this->options, $schema);
        } else {
            // TODO this.options.logger('Info', 'Schema was not updated since last run');
        }
    }
}
