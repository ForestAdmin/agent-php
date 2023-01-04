<?php

use ForestAdmin\AgentPHP\Agent\Serializer\Transformers\BaseTransformer;

test('setName() should set the name value', function () {
    $transformer = new BaseTransformer('foo');
    $transformer->setName('bar');

    expect(invokeProperty($transformer, 'name'))->toEqual('bar');
});
