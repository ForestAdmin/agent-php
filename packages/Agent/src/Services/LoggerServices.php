<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Closure;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class LoggerServices
{
    public array $levels = ['Debug', 'Info', 'Warn', 'Error'];

    protected Logger $defaultLogger;

    public function __construct(protected string $loggerLevel = 'Info', protected ?Closure $logger = null)
    {
        $this->defaultLogger = new Logger('forestadmin');
        $output = "[%datetime%] forestadmin.%level_name%: %message% %context% %extra%\n";
        $coloredFormatter = new ColoredLineFormatter(null, $output, null, false, true);
        // Create a handler
        $handler = new StreamHandler('php://stdout', Level::fromName($this->loggerLevel));
        $handler->setFormatter($coloredFormatter);
        $this->defaultLogger->pushHandler($handler);
    }

    public function log(string $level, string $message): void
    {
        $this->logger
            ? call_user_func(self::$logger, [$level, $message])
            : $this->defaultLogger->log(Level::fromName($level), $message);
    }
}
