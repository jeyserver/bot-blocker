<?php

namespace Arad\BotBlocker;

use Exception;
use Generator;

class LogFile
{
    /**
     * @var resource
     */
    protected $fd;

    public function __construct(string $filename)
    {
        $fd = fopen($filename, 'r');
        if (false === $fd) {
            throw new Exception();
        }
        $this->fd = $fd;
        fseek($this->fd, 0, SEEK_END);
    }

    public function __destruct()
    {
        fclose($this->fd);
    }

    /**
     * @return Generator<LogEntry>
     */
    public function read(): Generator
    {
        while (($line = stream_get_line($this->fd, 10 * 1024, PHP_EOL)) !== false) {
            yield new LogEntry($this, $line);
        }
    }
}
