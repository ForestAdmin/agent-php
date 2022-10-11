<?php

use ForestAdmin\AgentPHP\Agent\Http\ForestApiRequester;
use ForestAdmin\AgentPHP\Agent\Services\IpWhitelist;
use ForestAdmin\AgentPHP\DatasourceToolkit\Exceptions\ForestException;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophet;

function factoryIpWhitelist(array $rules = [], bool $active = true): object
{
    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
        ->get(Argument::type('string'))
        ->shouldBeCalled()
        ->willReturn(
            new Response(200, [], json_encode([
                'data' => [
                    'type'       => 'ip-whitelist-rules',
                    'id'         => '1',
                    'attributes' => [
                        'rules'            => $rules,
                        'use_ip_whitelist' => $active,
                    ],
                ],
            ], JSON_THROW_ON_ERROR))
        );

    return $forestApi->reveal();
}

test('isEnabled() should return true when there is rules and use_ip_whitelist is true', function () {
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $enabled = $ipWhitelist->isEnabled();

    expect($enabled)->toBeTrue();
});

test('isEnabled() should return false when there is no rules and use_ip_whitelist is false', function () {
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist());
    $enabled = $ipWhitelist->isEnabled();

    expect($enabled)->toBeFalse();
});

test('isIpMatchesAnyRule() should return true when the clien ip is equal of RULE_MATCH_IP rule', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchesAnyRule($ip);

    expect($match)->toBeTrue();
});

test('isIpMatchesAnyRule() should return true when the client ip is in the range of RULE_MATCH_RANGE rule', function () {
    $ip = '10.0.0.44';
    $rules = [
        [
            'type'      => 1,
            'ipMinimum' => '10.0.0.1',
            'ipMaximum' => '10.0.0.100',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchesAnyRule($ip);

    expect($match)->toBeTrue();
});

test('isIpMatchesAnyRule() should return true when the client ip is in the subnet of RULE_MATCH_SUBNET rule', function () {
    $ip = '200.10.10.20';
    $rules = [
        [
            'type'  => 2,
            'range' => '200.10.10.0/24',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchesAnyRule($ip);

    expect($match)->toBeTrue();
});

test('isIpMatchesAnyRule() should return false when the client ip does\'nt comply rules', function () {
    $ip = '200.200.100.100';
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
        [
            'type'      => 1,
            'ipMinimum' => '10.0.0.1',
            'ipMaximum' => '10.0.0.100',
        ],
        [
            'type'  => 2,
            'range' => '200.10.10.0/24',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchesAnyRule($ip);

    expect($match)->toBeFalse();
});

test('isIpMatchesAnyRule() should throw an error when the rule RULE_MATCH_IP rule', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type'  => 4,
            'range' => '200.10.10.0/24',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));

    expect(fn () => $ipWhitelist->isIpMatchesAnyRule($ip))->toThrow(\Exception::class, 'Invalid rule type');
});

test('isIpMatchIp() should return true when the client ip the RULE_MATCH_IP rules', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchIp($ip, $rules[0]['ip']);

    expect($match)->toBeTrue();
});

test('isIpMatchIp() should return true when the client ip the RULE_MATCH_IP rules (ipv6)', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '::1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchIp($ip, $rules[0]['ip']);

    expect($match)->toBeTrue();
});

test('isIpMatchIp() should return false when the client ip different of the RULE_MATCH_IP rules', function () {
    $ip = '192.168.1.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchIp($ip, $rules[0]['ip']);

    expect($match)->toBeFalse();
});

test('isSameIpVersion() should return true on a comparison of same ip', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isSameIpVersion($ip, $rules[0]['ip']);

    expect($match)->toBeTrue();
});

test('isSameIpVersion() should return false on a comparison of different ip', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '::1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isSameIpVersion($ip, $rules[0]['ip']);

    expect($match)->toBeFalse();
});

test('isBothLoopback() should return true on the same range', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '127.0.0.1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isBothLoopback($ip, $rules[0]['ip']);

    expect($match)->toBeTrue();
});

test('isBothLoopback() should return false on a different range', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '::2',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isBothLoopback($ip, $rules[0]['ip']);

    expect($match)->toBeFalse();
});

test('isBothLoopback() should return true of an ip into a range of another ip', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '::1',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isBothLoopback($ip, $rules[0]['ip']);

    expect($match)->toBeTrue();
});

test('isBothLoopback() should return false of an ip in a different range of another ip', function () {
    $ip = '127.0.0.1';
    $rules = [
        [
            'type' => 0,
            'ip'   => '::2',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isBothLoopback($ip, $rules[0]['ip']);

    expect($match)->toBeFalse();
});

test('isIpMatchRange() should return true of an ip into a range of another ip', function () {
    $ip = '10.0.0.5';
    $rules = [
        [
            'type'      => 1,
            'ipMinimum' => '10.0.0.1',
            'ipMaximum' => '10.0.0.100',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchRange($ip, $rules[0]['ipMinimum'], $rules[0]['ipMaximum']);

    expect($match)->toBeTrue();
});

test('isIpMatchRange() should return false of an ip in a different range of another ip', function () {
    $ip = '10.0.0.5';
    $rules = [
        [
            'type'      => 1,
            'ipMinimum' => '2002:a00:1::',
            'ipMaximum' => '2002:a00:64::',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchRange($ip, $rules[0]['ipMinimum'], $rules[0]['ipMaximum']);

    expect($match)->toBeFalse();
});

test('isIpMatchSubnet() should return true of an ip into a subnet of another ip', function () {
    $ip = '10.0.0.1';
    $rules = [
        [
            'type'  => 2,
            'range' => '10.0.0.0/24',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchSubnet($ip, $rules[0]['range']);

    expect($match)->toBeTrue();
});

test('isIpMatchSubnet() should return false of an ip in a different subnet of another ip', function () {
    $ip = '2002:a00:1::';
    $rules = [
        [
            'type'  => 2,
            'range' => '10.0.0.0/24',
        ],
    ];
    $ipWhitelist = new IpWhitelist(factoryIpWhitelist($rules));
    $match = $ipWhitelist->isIpMatchSubnet($ip, $rules[0]['range']);

    expect($match)->toBeFalse();
});

test('retrieve() should thrown a error when the api doesn\'t respond correctly', function () {
    $prophet = new Prophet();
    $forestApi = $prophet->prophesize(ForestApiRequester::class);
    $forestApi
         ->get(Argument::type('string'))
         ->shouldBeCalled()
         ->willThrow(new \RuntimeException());

    expect(fn () => new IpWhitelist($forestApi->reveal()))
        ->toThrow(ForestException::class, '🌳🌳🌳 Cannot retrieve the data from the Forest server. An error occured in Forest API');
});
