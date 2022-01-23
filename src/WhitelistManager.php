<?php

namespace Arad\BotBlocker;

class WhitelistManager
{
    /**
     * @var string[]
     */
    protected array $ips = [];

    public function add(string $ip): void
    {
        if (!$this->has($ip)) {
            $this->ips[] = $ip;
        }
    }

    public function has(string $ip): bool
    {
        return in_array($ip, $this->ips);
    }
}
