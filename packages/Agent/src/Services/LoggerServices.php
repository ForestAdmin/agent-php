<?php

namespace ForestAdmin\AgentPHP\Agent\Services;

use Closure;
use Monolog\Formatter\LineFormatter;
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
        $formatter = new LineFormatter($output, null, false, true);
        // Create a handler
        $handler = new StreamHandler('php://stdout', $this->getMonologLevel($this->loggerLevel));
        $handler->setFormatter($formatter);
        $this->defaultLogger->pushHandler($handler);
    }

    public function log(string $level, string $message): void
    {
        $this->logger
            ? call_user_func($this->logger, $level, $message)
            : $this->defaultLogger->log($this->getMonologLevel($this->loggerLevel), $message);
    }

    /**
     * @codeCoverageIgnore
     * Level::class don't exist in monolog v3.0
     */
    private function getMonologLevel(string $level): int|Level
    {
        if (class_exists(Level::class)) {
            return Level::fromName($level);
        } else {
            return (new \ReflectionClass(Logger::class))->getConstant(strtoupper($level));
        }
    }
}
