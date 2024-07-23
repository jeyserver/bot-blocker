<?php

namespace Arad\BotBlocker;

use IPLib\Address\AddressInterface;
use IPLib\Factory as IPLibFactory;

class WhitelistManager
{
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

    public function has(AddressInterface $ip): bool
    {
        return
            $this->csfAllowList->has($ip)
            or $this->selfIPs->has($ip)
            or $this->isLoopback($ip);
    }

    protected function isLoopback(AddressInterface $ip): bool
    {
        foreach ([
            IPLibFactory::parseRangeString('::1/128'),
            IPLibFactory::parseRangeString('127.0.0.0/8'),
        ] as $loopbackRange) {
            if ($loopbackRange && $loopbackRange->contains($ip)) {
                return true;
            }
        }

        return false;
    }
}
