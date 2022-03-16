<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;

class WPAdminRequestRule implements IRule
{
    public function check(LogEntry $entry): float
    {
        $path = $entry->getPath();
        if (null === $path) {
            return 0;
        }
        if ('/wp-admin/' === substr($path, 0, strlen('/wp-admin/'))) {
            return -1;
        }
        if ('/wp-json/yoast/' === substr($path, 0, strlen('/wp-json/yoast/'))) {
            return -1;
        }

        return 0;
    }
}
