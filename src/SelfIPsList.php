<?php

namespace Arad\BotBlocker;

use IPLib\Address\AddressInterface;
use IPLib\Factory as IPLibFactory;
use IPLib\Range\RangeInterface;

class SelfIPsList
{
    /**
     * @var RangeInterface[]
     */
    protected array $ipRanges = [];

    public function reload(): void
    {
        $output = shell_exec('hostname -I');
        if (!$output) {
            return;
        }
        foreach (explode(' ', trim($output)) as $payload) {
            $range = IPLibFactory::parseRangeString($payload);
            if ($range) {
                $this->ipRanges[] = $range;
            }
        }
    }

    public function has(AddressInterface $ip): bool
    {
        foreach ($this->ipRanges as $ipRange) {
            if ($ipRange->contains($ip)) {
                return true;
            }
        }

        return false;
    }
}
