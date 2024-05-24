<?php

use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Context\HookContext;
use ForestAdmin\AgentPHP\DatasourceCustomizer\Decorators\Hook\Hooks;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;

beforeEach(closure: function () {
    global $collection;

    $collection = new Collection(new Datasource(), 'Book');
});

test('executeBefore() should call all of them when multiple before hooks are defined', function ($caller) {
    global $collection;
    $firstHook = \Mockery::spy(fn () => true);
    $secondHook = \Mockery::spy(fn () => true);
    $hooks = new Hooks();
    $this->invokeProperty($hooks, 'before', [$firstHook, $secondHook]);

    $hooks->executeBefore(new HookContext($collection, $caller));

    $firstHook->shouldHaveBeenCalled();
    $secondHook->shouldHaveBeenCalled();
})->with('caller');

test('executeBefore() it should prevent the second hook to run when the first hook raise an error', function ($caller) {
    global $collection;
    $firstHook = \Mockery::spy(fn () => throw new Exception());
    $secondHook = \Mockery::spy(fn () => true);
    $hooks = new Hooks();
    $this->invokeProperty($hooks, 'before', [$firstHook, $secondHook]);

    expect(fn () => $hooks->executeBefore(new HookContext($collection, $caller)))->toThrow(Exception::class);
    $secondHook->shouldNotHaveBeenCalled();
})->with('caller');

test('executeBefore() it should not call the hook when after hook is defined', function ($caller) {
    global $collection;
    $firstHook = \Mockery::spy(fn () => throw new Exception());
    $hooks = new Hooks();
    $this->invokeProperty($hooks, 'after', [$firstHook]);

    $hooks->executeBefore(new HookContext($collection, $caller));

    $firstHook->shouldNotHaveBeenCalled();
})->with('caller');

test('executeAfter() should call all of them when multiple after hooks are defined', function ($caller) {
    global $collection;
    $firstHook = \Mockery::spy(fn () => true);
    $secondHook = \Mockery::spy(fn () => true);
    $hooks = new Hooks();
    $this->invokeProperty($hooks, 'after', [$firstHook, $secondHook]);

    $hooks->executeAfter(new HookContext($collection, $caller));

    $firstHook->shouldHaveBeenCalled();
    $secondHook->shouldHaveBeenCalled();
})->with('caller');

test('executeAfter() it should prevent the second hook to run when the first hook raise an error', function ($caller) {
    global $collection;
    $firstHook = \Mockery::spy(fn () => throw new Exception());
    $secondHook = \Mockery::spy(fn () => true);
    $hooks = new Hooks();
    $this->invokeProperty($hooks, 'after', [$firstHook, $secondHook]);

    expect(fn () => $hooks->executeAfter(new HookContext($collection, $caller)))->toThrow(Exception::class);
    $secondHook->shouldNotHaveBeenCalled();
})->with('caller');

test('executeAfter() it should not call the hook when before hook is defined', function ($caller) {
    global $collection;
    $firstHook = \Mockery::spy(fn () => throw new Exception());
    $hooks = new Hooks();
    $this->invokeProperty($hooks, 'before', [$firstHook]);

    $hooks->executeAfter(new HookContext($collection, $caller));

    $firstHook->shouldNotHaveBeenCalled();
})->with('caller');
