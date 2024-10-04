<?php

use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ConflictError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\ForbiddenError;
use ForestAdmin\AgentPHP\Agent\Http\Exceptions\RequireApproval;
use ForestAdmin\AgentPHP\Agent\Http\Request;
use ForestAdmin\AgentPHP\Agent\Services\SmartActionChecker;
use ForestAdmin\AgentPHP\Agent\Utils\QueryStringParser;
use ForestAdmin\AgentPHP\DatasourceToolkit\Collection;
use ForestAdmin\AgentPHP\DatasourceToolkit\Components\Query\Filters\Filter;
use ForestAdmin\AgentPHP\DatasourceToolkit\Datasource;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\ColumnSchema;
use ForestAdmin\AgentPHP\DatasourceToolkit\Schema\Concerns\PrimitiveType;
use ForestAdmin\AgentPHP\Tests\TestCase;

function smartActionCheckerFactory(TestCase $testCase, $requesterId = false, $requestWithAllRecordsIdsExcluded = false)
{
    $datasource = new Datasource();
    $collection = new Collection($datasource, 'Booking');
    $collection->addFields(
        [
            'id'    => new ColumnSchema(columnType: PrimitiveType::NUMBER, isPrimaryKey: true),
            'title' => new ColumnSchema(columnType: PrimitiveType::STRING),
        ]
    );
    $datasource->addCollection($collection);
    $testCase->buildAgent($datasource);

    $_POST = [
        'data' => [
            'attributes' => [
                'values'                   => [],
                'ids'                      => [1],
                'collection_name'          => 'Booking',
                'parent_collection_name'   => null,
                'parent_collection_id'     => null,
                'parent_association_name'  => null,
                'all_records'              => false,
                'all_records_subset_query' => [
                    'fields[Booking]' => 'id,title',
                    'page[number]'    => 1,
                    'page[size]'      => 15,
                    'sort'            => '-id',
                    'timezone'        => 'Europe/Paris',
                ],
                'all_records_ids_excluded' => [],
                'smart_action_id'          => 'Booking-Mark@@@as@@@live',
                'signed_approval_request'  => null,
            ],
            'type'       => 'custom-action-requests',
        ],
    ];

    if ($requesterId) {
        $_POST['data']['attributes']['requester_id'] = $requesterId;
        $_POST['data']['attributes']['signed_approval_request'] = 'AAABBBCCC';
    }

    if ($requestWithAllRecordsIdsExcluded) {
        $_POST['data']['attributes']['all_records'] = true;
        $_POST['data']['attributes']['all_records_ids_excluded'] = [1, 2, 3];
    }

    $request = Request::createFromGlobals();
    $smartAction = [
        'triggerEnabled'             => [],
        'triggerConditions'          => [],
        'approvalRequired'           => [],
        'approvalRequiredConditions' => [],
        'userApprovalEnabled'        => [],
        'userApprovalConditions'     => [],
        'selfApprovalEnabled'        => [],
    ];

    $testCase->bucket = compact('collection', 'request', 'smartAction');
}

test('canExecute() should return true when the user can trigger the action', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, ['triggerEnabled' => [1]]);
    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should return true when the user can trigger the action with trigger conditions', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;
    $smartAction = array_merge(
        $smartAction,
        [
            'triggerEnabled'    => [1],
            'triggerConditions' => [
                [
                    'filter' => [
                        'aggregator' => 'and',
                        'conditions' => [
                            [
                                'field'    => 'title',
                                'value'    => null,
                                'source'   => 'data',
                                'operator' => 'present',
                            ],
                        ],
                    ],
                    'roleId' => 1,
                ],
            ],
        ]
    );

    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn([
            [
                'value' => 1,
                'group' => [],
            ],
        ])
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should return true when the user can trigger the action with trigger conditions with all_records_ids_excluded not empty', function () {
    smartActionCheckerFactory($this, false, true);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;
    $smartAction = array_merge(
        $smartAction,
        [
            'triggerEnabled'    => [1],
            'triggerConditions' => [
                [
                    'filter' => [
                        'aggregator' => 'and',
                        'conditions' => [
                            [
                                'field'    => 'title',
                                'value'    => null,
                                'source'   => 'data',
                                'operator' => 'present',
                            ],
                        ],
                    ],
                    'roleId' => 1,
                ],
            ],
        ]
    );

    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn([
            [
                'value' => 1,
                'group' => [],
            ],
        ])
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should throw when the user try to trigger the action with approvalRequired and without approvalRequiredConditions', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'triggerEnabled'             => [1],
        'approvalRequired'           => [1],
        'approvalRequiredConditions' => [],
    ]);

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(RequireApproval::class, 'This action requires to be approved');
});

test('canExecute() should throw when the user try to trigger the action with approvalRequired and match approvalRequiredConditions', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'triggerEnabled'             => [1],
        'approvalRequired'           => [1],
        'approvalRequiredConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);

    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn([
            [
                'value' => 1,
                'group' => [],
            ],
        ])
        ->getMock();
    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(RequireApproval::class, 'This action requires to be approved');
});

test('canExecute() should return true when the user try to trigger the action with approvalRequired without triggerConditions and correct role into approvalRequired', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'triggerEnabled'             => [1],
        'approvalRequired'           => [1],
        'approvalRequiredConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);

    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn([
            [
                'value' => 0,
                'group' => [],
            ],
        ])
        ->getMock();
    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should return true when the user try to trigger the action with approvalRequired with triggerConditions and correct role into approvalRequired', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'triggerEnabled'             => [1],
        'triggerConditions'          => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'title',
                            'value'    => null,
                            'source'   => 'data',
                            'operator' => 'present',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
        'approvalRequired'           => [1],
        'approvalRequiredConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);

    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 0,
                    'group' => [],
                ],
            ],
            [
                [
                    'value' => 1,
                    'group' => [],
                ],
            ]
        )
        ->getMock();
    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should throw when the user roleId is not into triggerEnabled & approvalRequired', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'triggerEnabled'             => [1000],
        'triggerConditions'          => [],
        'approvalRequired'           => [1000],
        'approvalRequiredConditions' => [],
    ]);

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should throw when smart action doesn\'t match with triggerConditions & approvalRequiredConditions', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'triggerEnabled'             => [1],
        'triggerConditions'          => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'title',
                            'value'    => null,
                            'source'   => 'data',
                            'operator' => 'present',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
        'approvalRequired'           => [1],
        'approvalRequiredConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);

    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 0,
                    'group' => [],
                ],
            ],
            [
                [
                    'value' => 0,
                    'group' => [],
                ],
            ]
        )
        ->getMock();
    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should return true when the user can approve and there is no userApprovalConditions and requesterId is not the callerId', function () {
    smartActionCheckerFactory($this, 20);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalEnabled' => [1],
    ]);

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should return true when the user can approve and there is no userApprovalConditions and user roleId is present into selfApprovalEnabled', function () {
    smartActionCheckerFactory($this, 1);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalEnabled' => [1],
        'selfApprovalEnabled' => [1],
    ]);

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should return true when the user can approve and the condition match with userApprovalConditions and requesterId is not the callerId', function () {
    smartActionCheckerFactory($this, 20);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalEnabled'    => [1],
        'userApprovalConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);
    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 1,
                    'group' => [],
                ],
            ],
        )
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() should return true when the user can approve and the condition match with userApprovalConditions and user roleId is present into selfApprovalEnabled', function () {
    smartActionCheckerFactory($this, 1);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
        'userApprovalEnabled'    => [1],
        'selfApprovalEnabled'    => [1],
    ]);
    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 1,
                    'group' => [],
                ],
            ],
        )
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect($smartActionChecker->canExecute())->toBeTrue();
});

test('canExecute() throw when the user try to approve when there is no userApprovalConditions and requesterId is equal to the callerId', function () {
    smartActionCheckerFactory($this, 1);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should throw when the user try to approve and there is no userApprovalConditions and user roleId is not present into selfApprovalEnabled', function () {
    smartActionCheckerFactory($this, 1);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'selfApprovalEnabled' => [1000],
    ]);

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should throw when the user try to approve and the condition don\'t match with userApprovalConditions and requesterId is the callerId', function () {
    smartActionCheckerFactory($this, 1);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);
    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 1,
                    'group' => [],
                ],
            ],
        )
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should throw when the user try to approve and the condition don\'t match with userApprovalConditions and requesterId is not the callerId', function () {
    smartActionCheckerFactory($this, 20);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1000,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
    ]);
    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 0,
                    'group' => [],
                ],
            ],
        )
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should throw when the user try to approve and the condition don\'t match with userApprovalConditions and user roleId is not present into selfApprovalEnabled', function () {
    smartActionCheckerFactory($this, 1);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge($smartAction, [
        'userApprovalConditions' => [
            [
                'filter' => [
                    'aggregator' => 'and',
                    'conditions' => [
                        [
                            'field'    => 'id',
                            'value'    => 1000,
                            'source'   => 'data',
                            'operator' => 'equal',
                        ],
                    ],
                ],
                'roleId' => 1,
            ],
        ],
        'selfApprovalEnabled'    => [1000],
    ]);
    $collection = \Mockery::mock($collection)
        ->shouldReceive('aggregate')
        ->andReturn(
            [
                [
                    'value' => 0,
                    'group' => [],
                ],
            ],
        )
        ->getMock();

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ForbiddenError::class, 'You don\'t have the permission to trigger this action');
});

test('canExecute() should throw with an unknown operators', function () {
    smartActionCheckerFactory($this);
    ['collection' => $collection, 'request' => $request, 'smartAction' => $smartAction] = $this->bucket;

    $smartAction = array_merge(
        $smartAction,
        [
            'triggerEnabled'    => [1],
            'triggerConditions' => [
                [
                    'filter' => [
                        'aggregator' => 'and',
                        'conditions' => [
                            [
                                'field'    => 'title',
                                'value'    => null,
                                'source'   => 'data',
                                'operator' => 'unknown',
                            ],
                        ],
                    ],
                    'roleId' => 1,
                ],
            ],
        ]
    );

    $smartActionChecker = new SmartActionChecker($request, $collection, $smartAction, QueryStringParser::parseCaller($request), 1, new Filter());

    expect(fn () => $smartActionChecker->canExecute())->toThrow(ConflictError::class, 'The conditions to trigger this action cannot be verified. Please contact an administrator.');
});
