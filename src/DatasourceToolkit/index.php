<?php

use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../_example/src/register.php';

//$filter = new Filter();
Filter::override(search: 'toto');
//dump($filter);

/*
class CustomerData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?int $age = null,
    ) {}
}
$input = [
    'email' => 'brent@stitcher.io',
];

$data = new CustomerData(...$input);

$input = [
    'search' => 'Brent',
];
$filter = new Filter(...$input);
dd($data, $filter);
*/
