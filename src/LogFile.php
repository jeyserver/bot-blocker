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

    protected string $path;

    public function __construct(string $filename)
    {
        $this->path = $filename;
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

    public function getPath(): string
    {
        return $this->path;
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
