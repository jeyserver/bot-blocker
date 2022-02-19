<?php

namespace Arad\BotBlocker;

use DirectoryIterator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class PhpFpmMonitor implements IMonitorSystem, LoggerAwareInterface
{
    /** @var array<string|int,string> */
    protected array $systemdServices = [];
    /** @var array<int,int> */
    protected array $errorsCount = [];
    protected int $restartThreshold;
    protected LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        int $restartThreshold = 15
    ) {
        $this->logger = $logger;
        $this->restartThreshold = $restartThreshold;
        $this->detectPhpFpmServices();
        $this->logger->info('founded php fpm services:', ['services' => $this->systemdServices]);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function processEntry(LogEntry $entry): void
    {
        if (empty($this->systemdServices)) {
            return; // no php fpm service found!
        }
        $status = $entry->getStatus();
        if (null === $status) {
            return;
        }
        if (!in_array($status, [502, 503, 504])) {
            return;
        }
        $this->logger->debug('new server error occurred', ['code' => $status]);

        $this->errorsCount[$status] = $this->errorsCount[$status] ?? 0;
        ++$this->errorsCount[$status];

        if (array_sum($this->errorsCount) >= $this->restartThreshold) {
            $this->logger->info('restart php fpm services');
            $this->restartPhpFpmServies();
            $this->errorsCount = [];
        }
    }

    protected function restartPhpFpmServies(): void
    {
        foreach ($this->systemdServices as $phpVersion => $serviceName) {
            $command = 'systemctl restart '.$serviceName;
            $this->logger->info('run command:', ['command' => $command]);
            shell_exec($command);
            sleep(1);
        }
    }

    protected function detectPhpFpmServices(): void
    {
        $this->systemdServices = [];

        $systemdServicesDirectories = [
            '/etc/systemd/system/', '/lib/systemd/system/',
        ];
        foreach ($systemdServicesDirectories as $directoryPath) {
            $directoryIterator = new DirectoryIterator($directoryPath);
            if (!$directoryIterator->isDir()) {
                continue;
            }
            foreach ($directoryIterator as $file) {
                $serviceName = $file->getFileName();
                if (preg_match('/^php(\d\.\d)-fpm\.service$/', $serviceName, $matches) or preg_match('/^php\-fpm(\d+)\.service$/', $serviceName, $matches)) {
                    $this->systemdServices[$matches[1]] = $serviceName;
                }
            }
        }
    }
}
