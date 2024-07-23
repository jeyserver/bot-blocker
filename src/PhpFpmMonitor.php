<?php

namespace Jeyserver\BotBlocker;

use DirectoryIterator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class PhpFpmMonitor implements IMonitorSystem, LoggerAwareInterface
{
    /**
     * @var array<string|int,string>
     */
    protected array $systemdServices = [];

    /**
     * @var array<int,int>
     */
    protected array $errorsCount = [];

    protected int $restartThreshold;

    protected LoggerInterface $logger;

    /**
     * @var string[]
     */
    protected array $files = [];

    /**
     * @var string[]
     */
    protected array $lastFiles = [];

    protected ?int $lastRestart = null;

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
        if (!in_array($status, [502, 504])) {
            return;
        }
        $this->logger->debug('new server error occurred', ['code' => $status]);
        $path = $entry->getFile()->getPath();
        if (!in_array($path, $this->files)) {
            $this->files[] = $path;
        }

        $this->errorsCount[$status] = $this->errorsCount[$status] ?? 0;
        ++$this->errorsCount[$status];
        $now = time();
        sort($this->files);
        sort($this->lastFiles);
        if (
            array_sum($this->errorsCount) >= $this->restartThreshold
            and (
                null === $this->lastRestart
                or $now - $this->lastRestart > 60
                or ($now - $this->lastRestart > 10 and $this->lastFiles != $this->files)
            )
        ) {
            $this->logger->notice('restart php fpm services', [
                'lastRestart' => $this->lastRestart,
                'files' => $this->files,
            ]);
            $this->restartPhpFpmServies();
            $this->errorsCount = [];
            $this->lastRestart = $now;
            $this->lastFiles = $this->files;
            $this->files = [];
        }
    }

    protected function restartPhpFpmServies(): void
    {
        foreach ($this->systemdServices as $phpVersion => $serviceName) {
            $command = 'systemctl restart '.$serviceName;
            $this->logger->notice('run command:', ['command' => $command]);
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
