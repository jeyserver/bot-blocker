<?php

namespace Arad\BotBlocker;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class CsfFirewall implements IDefenseSystem, LoggerAwareInterface
{
    /**
     * @var array<string,int>
     */
    protected array $blocks = [];
    protected int $lastClear;
    protected LoggerInterface $logger;
    protected WhitelistManager $whiteList;

    public function __construct(
        WhitelistManager $whiteList,
        LoggerInterface $logger
    ) {
        if (class_exists('shell_exec')) {
            throw new Exception("This defense system needs 'shell_exec' function which is disabled");
        }
        $this->whiteList = $whiteList;
        $this->logger = $logger;
        $this->lastClear = time();
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
        $ttl = time() - $until;
        shell_exec("csf -td {$ip} {$ttl} \"Blocked by bot-blocker\"");
        $this->blocks[$ip] = $until;
    }

    public function unblock(string $ip): void
    {
        if (isset($this->blocks[$ip])) {
            unset($this->blocks[$ip]);
        }
        shell_exec("csf --temprmd {$ip}");
    }

    public function clear(): void
    {
        $now = time();
        if ($now - $this->lastClear < 10) {
            return;
        }
        foreach ($this->blocks as $ip => $until) {
            if ($until < $now) {
                $this->logger->debug('ip unlocked due to timeout', ['ip' => $ip, 'until' => $until]);
                unset($this->blocks[$ip]);
            }
        }
    }

    public function isBlocked(string $ip): ?int
    {
        return $this->blocks[$ip] ?? null;
    }
}
