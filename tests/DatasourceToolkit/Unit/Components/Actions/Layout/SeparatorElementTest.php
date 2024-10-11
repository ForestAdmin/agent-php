<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Actions\Layout\SeparatorElement;

test('Set the expected component value', function () {
    $separator = new SeparatorElement();

    expect($separator->getComponent())->toEqual('Separator');
});
