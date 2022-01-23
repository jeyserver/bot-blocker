<?php

namespace Arad\BotBlocker;

use Generator;
use Inotify\InotifyEvent;
use Inotify\InotifyEventCodeEnum;
use Inotify\InotifyProxy;
use Inotify\WatchedResource;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class LogsWatcher implements LoggerAwareInterface
{
    protected InotifyProxy $inotify;
    protected LoggerInterface $logger;

    /**
     * @var array<string,LogFile>
     */
    protected array $logFiles = [];

    public function __construct(InotifyProxy $inotify, LoggerInterface $logger)
    {
        $this->inotify = $inotify;
        $this->logger = $logger;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getLogsDirectory(): string
    {
        return '/var/log/nginx/domains/';
    }

    public function start(): void
    {
        $this->logger->debug('add logs directory to inotify watch');
        $resource = new WatchedResource($this->getLogsDirectory(), InotifyEventCodeEnum::ON_MODIFY()->getValue(), 'logs-dir');
        $this->inotify->addWatch($resource);
    }

    /**
     * @return Generator<LogEvent>
     */
    public function read(): Generator
    {
        while (true) { // @phpstan-ignore-line
            foreach ($this->inotify->read() as $event) {
                yield from $this->processInotifyEvent($event);
            }
        }
    }

    /**
     * @return Generator<LogEvent>
     */
    protected function processInotifyEvent(InotifyEvent $event): Generator
    {
        $this->logger->debug('got a inotify event', [$event]);
        if (!$this->isLogEvent($event)) {
            $this->logger->debug("it's not log event, skip");

            return;
        }
        $path = $event->getPathWithFile();
        if (!isset($this->logFiles[$path])) {
            $this->logger->debug('open the log file', ['path' => $path]);
            $this->logFiles[$path] = new LogFile($path);
        }
        $this->logger->debug('start reading new entries', ['path' => $path]);
        foreach ($this->logFiles[$path]->read() as $entry) {
            $this->logger->debug('readed log event', [$entry]);
            yield new LogEvent($entry);
        }
    }

    protected function isLogEvent(InotifyEvent $event): bool
    {
        return '.log' == substr($event->getFileName(), -4) and 'error.log' != substr($event->getFileName(), -9);
    }
}
