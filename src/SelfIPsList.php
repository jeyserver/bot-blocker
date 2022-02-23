<?php

namespace Arad\BotBlocker;

class SelfIPsList
{
    /**
     * @var string[]
     */
    protected array $ips = [];

    public function reload(): void
    {
        $output = shell_exec('hostname -I');
        if (!$output) {
            return;
        }
        $this->ips = explode(' ', trim($output));
    }

    public function has(string $ip): bool
    {
        return in_array($ip, $this->ips);
    }
}
