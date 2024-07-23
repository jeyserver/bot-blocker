<?php

namespace Jeyserver\BotBlocker\Rules;

use Jeyserver\BotBlocker\IDefenseSystem;
use Jeyserver\BotBlocker\IRule;
use Jeyserver\BotBlocker\LogEntry;

class AlreadyBlockedRule implements IRule
{
    protected IDefenseSystem $defenseSystem;

    public function __construct(IDefenseSystem $defenseSystem)
    {
        $this->defenseSystem = $defenseSystem;
    }

    public function check(LogEntry $entry): float
    {
        $ip = $entry->getRemoteHost();
        if (null === $ip) {
            return 0;
        }
        if (!$this->defenseSystem->isBlocked($ip)) {
            return 0;
        }

        return -1;
    }
}
