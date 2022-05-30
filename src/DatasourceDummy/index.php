<?php

require __DIR__ . '/../../vendor/autoload.php';
require 'register.php';

use ForestAdmin\AgentPHP\DatasourceDummy\DummyDatasource;

$dummyDatasource = new DummyDatasource();
