<?php

namespace Arad\BotBlocker;

interface IMonitorSystem
{
    public function processEntry(LogEntry $entry): void;
}
