<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;
use Arad\BotBlocker\WhitelistManager;

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
