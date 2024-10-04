<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\ForestSchemaIntrospection;
use JsonPath\JsonObject;

beforeEach(function () {
    $options = AGENT_OPTIONS;
    $options['schemaPath'] = 'tests/Datasets/.forestadmin-schema.json';
    new AgentFactory($options, []);

    $this->bucket['forestSchemaIntrospection'] = new ForestSchemaIntrospection();
});

test('getSchema() should return the forestadmin schema json file', function () {
    expect($this->bucket['forestSchemaIntrospection']->getSchema())->toEqual(new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')));
});

test('getFields() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields");

    expect($this->bucket['forestSchemaIntrospection']->getFields('Book'))->toEqual($fields[0]);
});

test('getFields() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getFields('Foo'))->toEqual([]);
});

test('getSmartFields() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == true and @.reference == null)]");

    expect($this->bucket['forestSchemaIntrospection']->getSmartFields('Book'))->toEqual(['reference' => $fields[0]]);
});

test('getSmartFields() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getSmartFields('Foo'))->toEqual([]);
});

test('getSmartActions() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].actions[*]");

    expect($this->bucket['forestSchemaIntrospection']->getSmartActions('Book'))->toEqual($fields);
});

test('getSmartActions() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getSmartActions('Foo'))->toEqual([]);
});

test('getSmartSegments() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].segments[*]");

    expect($this->bucket['forestSchemaIntrospection']->getSmartSegments('Book'))->toEqual($fields);
});

test('getSmartSegments() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getSmartSegments('Foo'))->toEqual([]);
});

test('getRelationships() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == false and @.reference != null)]");

    expect($this->bucket['forestSchemaIntrospection']->getRelationships('Book'))->toEqual(['user' => $fields[0]]);
});

test('getRelationships() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getRelationships('Foo'))->toEqual([]);
});

test('getSmartRelationships() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == true and @.reference != null)]");

    expect($this->bucket['forestSchemaIntrospection']->getSmartRelationships('Book'))->toEqual(['smartDemo' => $fields[0]]);
});

test('getSmartRelationships() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getSmartRelationships('Foo'))->toEqual([]);
});

test('getTypeByField() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.field == 'id')].type");

    expect($this->bucket['forestSchemaIntrospection']->getTypeByField('Book', 'id'))->toEqual($fields[0]);
});

test('getTypeByField() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getTypeByField('Foo', 'bar'))->toBeNull();
});

test('getRelatedData() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'User')].fields[?(@.relationship == 'HasMany' or @.relationship == 'BelongsToMany')].field");
    $smartRelationships = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'User')].fields[?(@.is_virtual == true and @.reference != null)]");
    foreach ($smartRelationships as $relationship) {
        if (is_array($relationship['type'])) {
            $fields[] = $relationship['field'];
        }
    }

    expect($this->bucket['forestSchemaIntrospection']->getRelatedData('User'))->toEqual($fields);
});

test('getRelatedData() should return an empty array when the collection doesn\'t exist', function () {
    expect($this->bucket['forestSchemaIntrospection']->getRelatedData('Foo'))->toEqual([]);
});
