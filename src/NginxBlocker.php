<?php

namespace Arad\BotBlocker;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use SplFileInfo;

class NginxBlocker implements IDefenseSystem, LoggerAwareInterface
{
    /**
     * @var array<string,int>
     */
    protected array $blocks = [];
    protected SplFileInfo $file;
    protected ?int $lastRewrite = null;
    protected int $rewriteWaitTime;
    protected WhitelistManager $whiteList;
    protected LoggerInterface $logger;
    protected int $lastClear;

    public function __construct(
        WhitelistManager $whiteList,
        LoggerInterface $logger,
        int $rewriteWaitTime = 60
    ) {
        $this->file = new SplFileInfo('/etc/nginx/blocked-ips.conf');
        $this->whiteList = $whiteList;
        $this->logger = $logger;
        $this->rewriteWaitTime = $rewriteWaitTime;
        $this->lastClear = time();
        $this->reload();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function block(string $ip, int $until): void
    {
        if ($until < time()) {
            throw new Exception();
        }
        if ($this->whiteList->has($ip)) {
            $this->logger->info('ip is whitelisted, ignore', ['ip' => $ip]);

            return;
        }
        $this->blocks[$ip] = $until;
        $this->rewrite();
    }

    public function unblock(string $ip): void
    {
        if (!isset($this->blocks[$ip])) {
            $this->logger->debug('ip is not blocked, ignore', ['ip' => $ip]);

            return;
        }
        unset($this->blocks[$ip]);
        $this->rewrite();
    }

    public function clear(): void
    {
        $now = time();
        if ($now - $this->lastClear < $this->rewriteWaitTime) {
            return;
        }
        foreach ($this->blocks as $ip => $until) {
            if ($until < $now) {
                $this->logger->debug('ip unlocked due to timeout', ['ip' => $ip, 'until' => $until]);
                unset($this->blocks[$ip]);
            }
        }
        $this->rewrite();
    }

    public function isBlocked(string $ip): ?int
    {
        return $this->blocks[$ip] ?? null;
    }

    protected function rewrite(): void
    {
        if (null !== $this->lastRewrite and time() - $this->lastRewrite < $this->rewriteWaitTime) {
            return;
        }
        $file = $this->file->openFile('w');
        foreach ($this->blocks as $ip => $until) {
            $file->fwrite("deny {$ip}; # until {$until}\n");
        }
        unset($file);
        $this->tryIncludeInNginx();
        $this->reloadNginx();
        $this->lastRewrite = time();
    }

    protected function reload(): void
    {
        $this->blocks = [];
        if (!$this->file->isFile()) {
            return;
        }
        $needToRewrite = false;
        $file = $this->file->openFile('r');
        while (!$file->eof()) {
            $line = $file->fgets();
            if (false == $line) {
                continue;
            }
            if (!preg_match("/^deny (.+);\s*\# until (\d+)$/", $line, $matches)) {
                continue;
            }
            $ip = strval($matches[1]);
            $until = intval($matches[2]);
            if ($this->whiteList->has($ip) or $until < time()) {
                $needToRewrite = true;
            } else {
                $this->blocks[$ip] = $until;
            }
        }
        unset($file);
        if ($needToRewrite) {
            $this->rewrite();
        }
    }

    protected function reloadNginx(): void
    {
        shell_exec('nginx -s reload');
    }

    protected function tryIncludeInNginx(): void
    {
        $includesPath = '/etc/nginx/nginx-includes.conf';
        if (!is_file($includesPath)) {
            return;
        }
        $includes = file_get_contents($includesPath);
        if (false === $includes or false !== strpos($includes, $this->file->getPath())) {
            return;
        }
        file_put_contents($includesPath, "\ninclude {$this->file->getPath()};\n", FILE_APPEND);
    }
}
