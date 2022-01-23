<?php

namespace Arad\BotBlocker;

use dnj\Filesystem\Local\File;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type Subnet array{int,int}
 */
class CsfAllowList implements LoggerAwareInterface
{
    /**
     * @var SortedList<int>
     */
    protected SortedList $ips;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->ips = new SortedList();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function reload(): void
    {
        $file = new File('/etc/csf/csf.allow');
        if (!$file->exists()) {
            return;
        }
        $lines = explode("\n", $file->read());
        foreach ($lines as $line) {
            try {
                $this->processLine($line);
            } catch (Exception $e) {
                $this->logger->error($e->__toString());
            }
        }
    }

    public function has(string $ip): bool
    {
        $ip = ip2long($ip);
        if (false === $ip) {
            return false;
        }

        return $this->ips->has($ip);
    }

    /**
     * @return \Generator<string>
     */
    public function getIPs(): \Generator
    {
        foreach ($this->ips as $ip) {
            $ip = long2ip($ip);
            if (false === $ip) {
                continue;
            }
            yield $ip;
        }
    }

    protected function processLine(string $line): void
    {
        $x = 0;
        for ($l = strlen($line); $x < $l; ++$x) {
            if ('#' == $line[$x]) {
                break;
            }
        }
        $line = trim(substr($line, 0, $x));
        if (empty($line)) {
            return;
        }
        if (false !== strpos($line, '|')) {
            return;
        }
        $subnet = $this->getSubnet($line);
        $this->addSubnet($subnet);
    }

    /**
     * @param Subnet $subnet
     */
    protected function addSubnet(array $subnet): void
    {
        for ($x = $subnet[0]; $x < $subnet[1]; ++$x) {
            $this->ips->add($x);
        }
    }

    /**
     * @return Subnet
     */
    protected function getSubnet(string $subnet): array
    {
        if (!preg_match("/^([\d\.]+)(?:\/(\d+))?$/", $subnet, $matches)) {
            throw new Exception("'{$subnet}' is not valid subnet");
        }
        $network = $matches[1];
        $netmask = isset($matches[2]) ? intval($matches[2]) : 32;
        if (!filter_var($network, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            throw new Exception("'{$subnet}' is not valid subnet");
        }
        if ($netmask > 32) {
            throw new Exception("'{$subnet}' is not valid subnet");
        }
        $start = ip2long($network);
        if (false === $start) {
            throw new Exception();
        }
        $end = $start + pow(2, 32 - $netmask);

        return [$start, $end];
    }
}
