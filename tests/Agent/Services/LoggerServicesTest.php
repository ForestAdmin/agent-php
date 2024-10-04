<?php

use ForestAdmin\AgentPHP\Agent\Services\LoggerServices;


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

test('log() should use the default logger if closure not defined', function () {
    $loggerServices = new LoggerServices();
    $logger = new Logger('test');
    $handle = fopen('php://memory', 'a+');
    $handler = new StreamHandler($handle, 'Info');
    $output = "[TEST] forestadmin.%level_name%: %message%";
    $formatter = new LineFormatter($output, null, false, true);
    $handler->setFormatter($formatter);
    $logger->pushHandler($handler);
    $this->invokeProperty($loggerServices, 'defaultLogger', $logger);
    $loggerServices->log('Info', 'test');
    fseek($handle, 0);

    expect(fread($handle, 100))->toEqual('[TEST] forestadmin.INFO: test');
});

test('log() should use the closure when it defined', function () {
    $fp = fopen("php://memory", 'a+');
    $loggerServices = new LoggerServices(
        'Info',
        fn ($level, $message) => fwrite($fp, "CUSTOM : $message")
    );

    $loggerServices->log('Info', 'test');
    rewind($fp);

    expect(stream_get_contents($fp))->toEqual('CUSTOM : test');
    fclose($fp);
});
