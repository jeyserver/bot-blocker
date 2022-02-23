<?php

namespace Arad\BotBlocker;

class WhitelistManager
{
    /**
     * @var string[]
     */
    protected array $ips = [];

    protected CsfAllowList $csfAllowList;
    protected SelfIPsList $selfIPs;

    public function __construct(
        CsfAllowList $csfAllowList,
        SelfIPsList $selfIPs
    ) {
        $this->csfAllowList = $csfAllowList;
        $this->selfIPs = $selfIPs;
        $this->csfAllowList->reload();
        $this->selfIPs->reload();
    }

    public function add(string $ip): void
    {
        if (!$this->has($ip)) {
            $this->ips[] = $ip;
        }
    }

    public function has(string $ip): bool
    {
        return
            in_array($ip, $this->ips) or
            $this->csfAllowList->has($ip) or
            $this->selfIPs->has($ip) or
            $this->isLoopback($ip);
    }

    protected function isLoopback(string $ip): bool
    {
        $start = 2130706432; // ip2long("127.0.0.0");
        $end = 2147483648; // $start + pow(2, 32 - 8);
        $long = ip2long($ip);
        if (false === $long) {
            throw new Exception();
        }

        return $long >= $start and $long < $end;
    }
}
