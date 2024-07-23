<?php

namespace Jeyserver\BotBlocker;

use IPLib\Address\AddressInterface;

interface IDefenseSystem
{
    public function block(AddressInterface $ip, int $until): void;

    public function unblock(AddressInterface $ip): void;

    public function clear(): void;

    public function isBlocked(AddressInterface $ip): ?int;
}
