<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Caller;

dataset('caller', function () {
    yield $caller = new Caller(
        id: 1,
        email: 'sarah.connor@skynet.com',
        firstName: 'sarah',
        lastName: 'connor',
        team: 'poisonous mushrooms',
        renderingId: 1,
        tags: [],
        timezone: 'Europe/Paris',
        role: 'dev'
    );
});
