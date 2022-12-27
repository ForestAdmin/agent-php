<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Services\ForestSchemaInstrospection;
use JsonPath\JsonObject;

dataset('introspection', function () {
    //function factoryForestSchemaInstrospection() {
    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'cacheDir'     => sys_get_temp_dir() . '/forest-cache',
        'authSecret'   => AUTH_SECRET,
        'isProduction' => false,
        'debug'        => false,
        'schemaPath'   => 'tests/Datasets/.forestadmin-schema.json',
    ];
    new AgentFactory($options, []);

    yield new ForestSchemaInstrospection();
});

test('getSchema() should return the forestadmin schema json file', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getSchema())->toEqual(new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')));
})->with('introspection');

test('getFields() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields");

    expect($instrospection->getFields('Book'))->toEqual($fields[0]);
})->with('introspection');

test('getFields() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getFields('Foo'))->toEqual([]);
})->with('introspection');

test('getSmartFields() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == true and @.reference == null)]");

    expect($instrospection->getSmartFields('Book'))->toEqual(['reference' => $fields[0]]);
})->with('introspection');

test('getSmartFields() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getSmartFields('Foo'))->toEqual([]);
})->with('introspection');

test('getSmartActions() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].actions[*]");

    expect($instrospection->getSmartActions('Book'))->toEqual($fields);
})->with('introspection');

test('getSmartActions() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getSmartActions('Foo'))->toEqual([]);
})->with('introspection');

test('getSmartSegments() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].segments[*]");

    expect($instrospection->getSmartSegments('Book'))->toEqual($fields);
})->with('introspection');

test('getSmartSegments() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getSmartSegments('Foo'))->toEqual([]);
})->with('introspection');

test('getRelationships() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == false and @.reference != null)]");

    expect($instrospection->getRelationships('Book'))->toEqual(['user' => $fields[0]]);
})->with('introspection');

test('getRelationships() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getRelationships('Foo'))->toEqual([]);
})->with('introspection');

test('getSmartRelationships() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.is_virtual == true and @.reference != null)]");

    expect($instrospection->getSmartRelationships('Book'))->toEqual(['smartDemo' => $fields[0]]);
})->with('introspection');

test('getSmartRelationships() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getSmartRelationships('Foo'))->toEqual([]);
})->with('introspection');

test('getTypeByField() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'Book')].fields[?(@.field == 'id')].type");

    expect($instrospection->getTypeByField('Book', 'id'))->toEqual($fields[0]);
})->with('introspection');

test('getTypeByField() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getTypeByField('Foo', 'bar'))->toBeNull();
})->with('introspection');

test('getRelatedData() should return the list of the fields of a collection', function (ForestSchemaInstrospection $instrospection) {
    $fields = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'User')].fields[?(@.relationship == 'HasMany' or @.relationship == 'BelongsToMany')].field");
    $smartRelationships = (new JsonObject(file_get_contents('tests/Datasets/.forestadmin-schema.json')))->get("$..collections[?(@.name == 'User')].fields[?(@.is_virtual == true and @.reference != null)]");
    foreach ($smartRelationships as $relationship) {
        if (is_array($relationship['type'])) {
            $fields[] = $relationship['field'];
        }
    }

    expect($instrospection->getRelatedData('User'))->toEqual($fields);
})->with('introspection');

test('getRelatedData() should return an empty array when the collection doesn\'t exist', function (ForestSchemaInstrospection $instrospection) {
    expect($instrospection->getRelatedData('Foo'))->toEqual([]);
})->with('introspection');
