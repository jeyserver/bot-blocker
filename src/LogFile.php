<?php

namespace Arad\BotBlocker;

use Exception;
use Generator;
use Kassner\LogParser\FormatException;

class LogFile
{
    /**
     * @var resource
     */
    protected $fd;

    protected string $path;
    protected int $inode;

    public function __construct(string $filename)
    {
        $this->path = $filename;
        $this->reopen();
    }

    public function reopen(): void
    {
        if (isset($this->fd)) { // @phpstan-ignore-line
            fclose($this->fd);
        }
        $fd = fopen($this->path, 'r');
        if (false === $fd) {
            throw new Exception('can not call fopen on file: '.$this->path);
        }
        $this->fd = $fd;
        fseek($this->fd, 0, SEEK_END);
        $inode = fileinode($this->path);
        if (false === $inode) {
            throw new Exception('can not call fileinode on file: '.$this->path);
        }
        $this->inode = $inode;
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
        $stat = stat($this->path);
        if (false === $stat) {
            throw new Exception('can not call stat on file: '.$this->path);
        }
        if ($stat['ino'] != $this->inode or $stat['size'] < ftell($this->fd)) {
            $this->reopen();
        }
        $line = stream_get_line($this->fd, 10 * 1024, PHP_EOL);
        while (false !== $line) {
            $entry = null;
            try {
                $entry = new LogEntry($this, $line);
            } catch (FormatException $e) {
            }
            if (null !== $entry) {
                yield $entry;
            }
            $line = stream_get_line($this->fd, 10 * 1024, PHP_EOL);
        }
    }
}
