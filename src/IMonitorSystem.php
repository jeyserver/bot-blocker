<?php

namespace Jeyserver\BotBlocker;

interface IMonitorSystem
{
    public function processEntry(LogEntry $entry): void;
}
