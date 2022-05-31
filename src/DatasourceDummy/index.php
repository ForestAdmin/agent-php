<?php

require __DIR__ . '/../../vendor/autoload.php';
require 'register.php';

use ForestAdmin\AgentPHP\DatasourceDummy\DummyDatasource;

$dummyDatasource = new DummyDatasource();

$emitter = new \ForestAdmin\AgentPHP\Agent\SchemaEmitter();
$emitter->getSerializedSchema(
    [
        'prefix'       => 'forest',
        'isProduction' => false,
    ],
    $dummyDatasource
);


