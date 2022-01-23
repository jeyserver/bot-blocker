<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ServerErrorRule implements IRule, LoggerAwareInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
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

    public function check(LogEntry $entry): float
    {
        $status = $entry->getStatus();
        if (null === $status) {
            return 0;
        }
        if (!in_array($status, [500, 502, 503, 504, 403])) {
            return 0;
        }
        $this->logger->debug("http status is {$status}");

        return -1;
    }
}
