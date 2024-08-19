<?php

use ForestAdmin\AgentPHP\Agent\Services\CacheServices;
use ForestAdmin\AgentPHP\Tests\TestCase;

const BEARER = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6eyJzb21ldGhpbmciOiJ0YWdWYWx1ZSJ9LCJ0aW1lem9uZSI6IkV1cm9wZS9QYXJpcyIsInBlcm1pc3Npb25MZXZlbCI6ImFkbWluIn0.yCAGVg2Ef4a6uDbM6_VjlFobFwACJnyFtjkbo5lkEi4';

const AUTH_SECRET = '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d';

const SECRET = '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d';

const FOREST_PERMISSIONS_EXPIRATION_IN_SECONDS = 60;

define("AGENT_OPTIONS", [
    'projectDir'            => sys_get_temp_dir(),
//    'cacheDir'              => sys_get_temp_dir() . '/forest-cache',
    'schemaPath'            => sys_get_temp_dir() . '/.forestadmin-schema.json',
    'authSecret'            => AUTH_SECRET,
    'envSecret'             => SECRET,
    'isProduction'          => false,
    'permissionExpiration'  => FOREST_PERMISSIONS_EXPIRATION_IN_SECONDS,
]);

uses(TestCase::class)->in(
    'Agent',
    'DatasourceCustomizer',
    'DatasourceToolkit',
    'DatasourceDoctrine',
    'DatasourceEloquent',
    'BaseDatasource'
);

uses()
    ->beforeEach(
        function () {
            $cache = new CacheServices();
            $cache->flush();

            $_GET = [];
            $_POST = [];
        }
    )->in('Agent', 'DatasourceToolkit', 'DatasourceDoctrine', 'DatasourceEloquent', 'BaseDatasource', 'DatasourceCustomizer');
