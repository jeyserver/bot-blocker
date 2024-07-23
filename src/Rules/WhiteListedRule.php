<?php

namespace Jeyserver\BotBlocker\Rules;

use Jeyserver\BotBlocker\IRule;
use Jeyserver\BotBlocker\LogEntry;
use Jeyserver\BotBlocker\WhitelistManager;

class WhiteListedRule implements IRule
{
    protected WhitelistManager $whiteList;

    public function __construct(WhitelistManager $whiteList)
    {
        $this->whiteList = $whiteList;
    }

    public function check(LogEntry $entry): float
    {
        $ip = $entry->getRemoteHost();
        if (null === $ip) {
            return 0;
        }

        return $this->whiteList->has($ip) ? -1 : 0;
    }
}
