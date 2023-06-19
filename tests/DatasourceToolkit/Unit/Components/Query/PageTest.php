<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Page;

test('getter should work', function () {
    $page = new Page(20, 10);
    expect($page->getLimit())->toEqual(10)
        ->and($page->getOffset())->toEqual(20);
});

test('apply should work', function () {
    $page = new Page(5, 3);
    $records = [
        ['id', 1],
        ['id', 2],
        ['id', 3],
        ['id', 4],
        ['id', 5],
        ['id', 6],
        ['id', 7],
        ['id', 8],
    ];

    expect($page->apply($records))->toEqual([['id', 6],['id', 7],['id', 8]]);
});
