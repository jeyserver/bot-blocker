<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\LogEntry;

class WPBruteForceRule extends BruteForceRule
{
    public function check(LogEntry $entry): float
    {
        $path = $entry->getPath();
        if (null === $path or !preg_match("/\/(?:xmlrpc|wp-login)\.php$/", $path)) {
            return 0;
        }

        return parent::check($entry);
    }
}
