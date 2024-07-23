<?php

namespace Jeyserver\BotBlocker;

class LogEvent
{
    protected LogEntry $entry;

    public function __construct(LogEntry $entry)
    {
        $this->entry = $entry;
    }

    public function getEntry(): LogEntry
    {
        return $this->entry;
    }
}
