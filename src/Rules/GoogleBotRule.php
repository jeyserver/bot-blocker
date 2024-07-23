<?php

namespace Jeyserver\BotBlocker\Rules;

use Jeyserver\BotBlocker\GoogleBotDetector;
use Jeyserver\BotBlocker\IRule;
use Jeyserver\BotBlocker\LogEntry;

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
