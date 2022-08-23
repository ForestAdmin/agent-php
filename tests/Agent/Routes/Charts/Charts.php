<?php

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\Routes\Charts\Charts;
use ForestAdmin\AgentPHP\Agent\Services\ForestAdminHttpDriverServices;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Charts\ValueChart;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\ManyToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Decorators\Schema\Relations\OneToManySchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;

function factory($args = []): Datasource
{
    $datasource = new Datasource();
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MSwiZW1haWwiOiJqb2huLmRvZUBkb21haW4uY29tIiwiZmlyc3ROYW1lIjoiSm9obiIsImxhc3ROYW1lIjoiRG9lIiwidGVhbSI6IkRldmVsb3BlcnMiLCJyZW5kZXJpbmdJZCI6IjEwIiwidGFncyI6W10sInRpbWV6b25lIjoiRXVyb3BlL1BhcmlzIn0.-zTadg2QjQSH6b5kZxa4kSfBCZBAZq9T4ZJqAkTAQcs';

    $collectionBooks = new Collection($datasource, 'books');
    $collectionBooks->addFields(
        [
            'id'          => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title'       => new ColumnSchema(columnType: PrimitiveType::STRING),
            'price'       => new ColumnSchema(columnType: PrimitiveType::NUMBER),
            'date'        => new ColumnSchema(columnType: PrimitiveType::DATE),
            'reviews'     => new ManyToManySchema(
                foreignKey: 'review_id',
                foreignKeyTarget: 'id',
                throughTable: 'bookReview',
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews'
            ),
            'bookReviews' => new OneToManySchema(
                originKey: 'book_id',
                originKeyTarget: 'id',
                foreignCollection: 'reviews',
            ),
        ]
    );

    $collectionReviews = new Collection($datasource, 'reviews');
    $collectionReviews->addFields(
        [
            'id'     => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'author' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );

    if (isset($args['books']['results'])) {
        $collectionBooks = mock($collectionBooks)
            ->shouldReceive('aggregate')
            ->andReturn($args['books']['results'])
            ->getMock();
    }

    $datasource->addCollection($collectionBooks);
    $datasource->addCollection($collectionReviews);

    $options = [
        'projectDir'   => sys_get_temp_dir(),
        'envSecret'    => '34b6d9b573e160b957244c1082619bc5a9e36ee8abae5fe7d15991d08ac9f31d',
        'isProduction' => false,
    ];
    (new AgentFactory($options))->addDatasources([$datasource]);

    return $datasource;
}

test('setType() should return type string', function () {
    $chart = new Charts(new ForestAdminHttpDriverServices());
    $chart->setType('Value');

    expect($chart->getType())->toEqual('Value');
});

test('setType() should throw a ForestException when the type does not exist in the chartTypes list', function () {
    $chart = new Charts(new ForestAdminHttpDriverServices());

    expect(fn () => $chart->setType('Maps'))->toThrow(ForestException::class, '🌳🌳🌳 Invalid Chart type Maps');
});

test('makeValue() should return a ValueChart', function () {
    $datasource = factory(
        [
            'books' => [
                'results' => [
                    [
                        'sum' => 10,
                    ],
                ],
            ],
        ]
    );

    $_GET = [
        'type'            => 'Value',
        'collection'      => 'books',
        'aggregate_field' => 'price',
        'aggregate'       => 'Sum',
        'filters'         => null,
        'timezone'        => 'Europe/Paris',
    ];

    $chart = new Charts(new ForestAdminHttpDriverServices());

    expect($chart->handleRequest(['collectionName' => 'books']))
        ->toBeArray()
        ->toEqual(
            [
                'renderChart' => true,
                'content'     => new ValueChart(10),
            ]
        );
});


