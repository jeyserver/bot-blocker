<?php

namespace Arad\BotBlocker;

interface IDefenseSystem
{
    public function block(string $ip, int $until): void;

    public function unblock(string $ip): void;

    public function clear(): void;

    public function isBlocked(string $ip): ?int;
}
