<?php

namespace Arad\BotBlocker;

class GoogleBotDetector
{
    /**
     * @var string[]
     */
    protected array $ips = [];

    public function isGoogleBot(LogEntry $entry): bool
    {
        $ip = $entry->getRemoteHost();
        if (null !== $ip and in_array($ip, $this->ips)) {
            return true;
        }
        $agent = $entry->getUserAgent();
        if (null === $agent or false === stripos($agent, 'googlebot')) {
            return false;
        }
        if (null !== $ip) {
            $this->ips[] = $ip;
        }

        return true;
    }

    /**
     * @return string[]
     */
    public function getIPs(): array
    {
        return $this->ips;
    }
}
