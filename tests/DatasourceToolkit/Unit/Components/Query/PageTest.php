<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;

test('getter should work', function () {
    $page = new Page(20, 10);
    expect($page->getLimit())->toEqual(10)
        ->and($page->getOffset())->toEqual(20);
});
