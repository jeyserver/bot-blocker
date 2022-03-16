<?php

namespace Arad\BotBlocker;

use Exception;
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

    /**
     * @return string[]
     */
    public function getLogsDirectories(): array
    {
        $directories = [];
        foreach ([
            '/var/log/nginx/',
            '/var/log/nginx/domains/',
            '/var/log/httpd/',
            '/var/log/httpd/domains/',
        ] as $path) {
            if (is_dir($path)) {
                $directories[] = $path;
            }
        }
        if (empty($directories)) {
            throw new Exception('no path cannot find for webserver logs');
        }

        return $directories;
    }

    public function start(): void
    {
        /**
         * @var int
         */
        $events = InotifyEventCodeEnum::ON_MODIFY()->getValue();
        foreach ($this->getLogsDirectories() as $directory) {
            $this->logger->debug("add '{$directory}' directory to inotify watch");
            $resource = new WatchedResource($directory, $events, 'logs-dir');
            $this->inotify->addWatch($resource);
        }
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
            try {
                $this->logFiles[$path] = new LogFile($path);
            } catch (Exception $e) {
                $this->logger->error("Error in opening of '{$path}'", ['exception' => $e->__toString()]);

                return;
            }
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
