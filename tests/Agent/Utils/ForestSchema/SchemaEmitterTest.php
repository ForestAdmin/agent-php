<?php

use ForestAdmin\AgentPHP\Agent\Utils\ForestSchema\SchemaEmitter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

test('getSerializedSchema() should log warning id schema doesn\'t exist in production', function () {
    $datasource = new Datasource();
    $fp = fopen("php://memory", 'a+');
    $this->buildAgent($datasource);
    $this->agent->createAgent(
        [
            'isProduction' => true,
            'schemaPath'   => 'fake/path',
            'logger'       => fn ($level, $message) => fwrite($fp, $message),
        ]
    );

    SchemaEmitter::getSerializedSchema($datasource);

    rewind($fp);
    expect(stream_get_contents($fp))->toEqual('The .forestadmin-schema.json file doesn\'t exist');
    fclose($fp);
});
