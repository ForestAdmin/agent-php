<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\DatasourceDecorator;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Empty\EmptyCollection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

test('getCharts() should call the childDatasource corresponding method', function () {
    $datasource = new Datasource();
    invokeProperty($datasource, 'charts', collect(['myChart']));
    $datasourceDecorator = new DatasourceDecorator($datasource, EmptyCollection::class);

    expect($datasourceDecorator->getCharts())->toEqual(collect(['myChart']));
});
