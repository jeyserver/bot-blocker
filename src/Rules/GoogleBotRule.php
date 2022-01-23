<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\GoogleBotDetector;
use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;

class GoogleBotRule implements IRule
{
    protected GoogleBotDetector $googleBotDetector;

    public function __construct(GoogleBotDetector $googleBotDetector)
    {
        $this->googleBotDetector = $googleBotDetector;
    }

    public function check(LogEntry $entry): float
    {
        return $this->googleBotDetector->isGoogleBot($entry) ? -1 : 0;
    }
}
