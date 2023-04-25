<?php


use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Computed\Utils\ComputeField;

test('transformUniqueValues() should work', function () {
    $inputs = [1, null, 2, 2, null, 666];
    $result = ComputeField::transformUniqueValues(
        $inputs,
        fn ($item) => collect($item)->map(fn ($value) => $value * 2)
    );

    expect($result)->toEqual([2, null, 4, 4, null, 1332]);
});
