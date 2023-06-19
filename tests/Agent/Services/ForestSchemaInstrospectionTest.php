<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\ForestSchemaIntrospection;
use JsonPath\JsonObject;

function introspection()
{
    $options = AGENT_OPTIONS;
    $options['schemaPath'] = 'tests/Datasets/.forestadmin-schema.json';
    new AgentFactory($options, []);

    return new ForestSchemaIntrospection();
}

test('getSchema() should return the forestadmin schema json file', function () {
    expect(introspection()->getSchema())->toEqual(new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')));
});

test('getFields() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields");

    expect(introspection()->getFields('Book'))->toEqual($fields[0]);
});

test('getFields() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getFields('Foo'))->toEqual([]);
});

test('getSmartFields() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == true and @.reference == null)]");

    expect(introspection()->getSmartFields('Book'))->toEqual(['reference' => $fields[0]]);
});

test('getSmartFields() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getSmartFields('Foo'))->toEqual([]);
});

test('getSmartActions() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].actions[*]");

    expect(introspection()->getSmartActions('Book'))->toEqual($fields);
});

test('getSmartActions() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getSmartActions('Foo'))->toEqual([]);
});

test('getSmartSegments() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].segments[*]");

    expect(introspection()->getSmartSegments('Book'))->toEqual($fields);
});

test('getSmartSegments() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getSmartSegments('Foo'))->toEqual([]);
});

test('getRelationships() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == false and @.reference != null)]");

    expect(introspection()->getRelationships('Book'))->toEqual(['user' => $fields[0]]);
});

test('getRelationships() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getRelationships('Foo'))->toEqual([]);
});

test('getSmartRelationships() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == true and @.reference != null)]");

    expect(introspection()->getSmartRelationships('Book'))->toEqual(['smartDemo' => $fields[0]]);
});

test('getSmartRelationships() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getSmartRelationships('Foo'))->toEqual([]);
});

test('getTypeByField() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.field == 'id')].type");

    expect(introspection()->getTypeByField('Book', 'id'))->toEqual($fields[0]);
});

test('getTypeByField() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getTypeByField('Foo', 'bar'))->toBeNull();
});

test('getRelatedData() should return the list of the fields of a collection', function () {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'User')].fields[?(@.relationship == 'HasMany' or @.relationship == 'BelongsToMany')].field");
    $smartRelationships = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'User')].fields[?(@.is_virtual == true and @.reference != null)]");
    foreach ($smartRelationships as $relationship) {
        if (is_array($relationship['type'])) {
            $fields[] = $relationship['field'];
        }
    }

    expect(introspection()->getRelatedData('User'))->toEqual($fields);
});

test('getRelatedData() should return an empty array when the collection doesn\'t exist', function () {
    expect(introspection()->getRelatedData('Foo'))->toEqual([]);
});
