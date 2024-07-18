<?php

use ForestAdmin\AgentPHP\DatasourceEloquent\Utils\ClassFinder;

describe('class finder', function () {
    test('should return the list of the models that extend Eloquent Moden', function () {
        $finder = new ClassFinder(__DIR__ . '/..');

        expect($finder->getModelsInNamespace('App'))->toEqual([
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Author',
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Book',
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\BookReview',
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Car',
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Owner',
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\Review',
            'ForestAdmin\AgentPHP\Tests\DatasourceEloquent\Models\User',
        ]);
    });

    test('should return an empty list when there is no model in the namespace', function () {
        $finder = new ClassFinder(__DIR__ . '/..');

        expect($finder->getModelsInNamespace('Fake'))->toEqual([]);
    });
});
