<?php

namespace Jeyserver\BotBlocker;

interface IRule
{
    /**
     * @return float -1 means this request is fully good and 1 is this user must be blocked
     */
    public function check(LogEntry $entry): float;
}
