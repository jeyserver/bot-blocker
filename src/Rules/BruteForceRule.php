<?php

namespace Jeyserver\BotBlocker\Rules;

use Jeyserver\BotBlocker\IRule;
use Jeyserver\BotBlocker\LogEntry;

class BruteForceRule implements IRule
{
    protected int $since;
    protected int $maxRequests;
    protected int $period;

    /**
     * @var array<string,int>
     */
    protected array $ips = [];

    public function __construct(int $maxRequests, int $period)
    {
        $this->since = time();
        $this->maxRequests = $maxRequests;
        $this->period = $period;
    }

    public function getLastReset(): int
    {
        return $this->since;
    }

    public function setMaxRequests(int $maxRequests): void
    {
        $this->maxRequests = $maxRequests;
    }

    public function getMaxRequests(): int
    {
        return $this->maxRequests;
    }

    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function reset(): void
    {
        $this->since = time();
        $this->ips = [];
    }

    public function check(LogEntry $entry): float
    {
        if (time() - $this->since > $this->period) {
            $this->reset();
        }

        $ip = $entry->getRemoteHost();
        if (null === $ip) {
            return 0;
        }
        $ipString = $ip->toString();
        $this->ips[$ipString] = ($this->ips[$ipString] ?? 0) + 1;

        return $this->ips[$ipString] / $this->maxRequests;
    }
}
