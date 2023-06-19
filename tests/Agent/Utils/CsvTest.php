<?php

use ForestAdmin\AgentPHP\Agent\Utils\Csv;

test('make() should return a string with header and a line per record', function () {
    $header = ['id', 'label'];
    $data = [
        [
            'id'    => 1,
            'label' => 'foo',
        ],
        [
            'id'    => 2,
            'label' => 'bar',
        ],
    ];

    expect(Csv::make($data, $header))->toEqual("id,label\n1,foo\n2,bar\n");
});

test('formatField() on bool should return an integer', function () {
    expect(Csv::formatField(['active' => true]))->toEqual([1]);
});

test('formatField() on array should return an empty string', function () {
    expect(Csv::formatField(['comments' => []]))->toEqual(['']);
});

test('formatField() on DateTime should return a date format Y-m-d h:i:s', function () {
    expect(Csv::formatField(['date' => new \DateTime('2022-01-01T10:10:00')]))->toEqual(['2022-01-01 10:10:00']);
});
