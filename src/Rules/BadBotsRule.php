<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class BadBotsRule implements IRule, LoggerAwareInterface
{
    protected LoggerInterface $logger;

    /**
     * @var string[]
     */
    protected array $bads = [
        'X11; Ubuntu; Linux x86_64; rv:62.0',
        'SemrushBot',
        'HeadlessChrome',
        'sogou',
        'DuckDuckBot',
        'DotBot',
        'Yandex',
    ];

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
        $agent = $entry->getUserAgent();
        if (null === $agent) {
            return 0;
        }
        foreach ($this->bads as $bad) {
            if (false !== stripos($agent, $bad)) {
                $this->logger->debug("user-agent contains '{$bad}' keyword");

                return 1;
            }
        }

        return 0;
    }
}
