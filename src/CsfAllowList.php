<?php

namespace Jeyserver\BotBlocker;

use dnj\Filesystem\Local\File;
use Exception;
use IPLib\Address\AddressInterface;
use IPLib\Factory as IPLibFactory;
use IPLib\Range\RangeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * @phpstan-type Subnet array{int,int}
 */
class CsfAllowList implements LoggerAwareInterface
{
    /** @var RangeInterface[] */
    protected array $ipRanges = [];

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $this->ipRanges = [];
        $files = [
            new File('/etc/csf/csf.allow'),
            new File('/etc/csf/csf.ignore'),
        ];
        foreach ($files as $file) {
            $this->addFromFile($file);
        }
    }

    public function addFromFile(File $file): void
    {
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

    public function has(AddressInterface $ip): bool
    {
        foreach ($this->ipRanges as $ipRange) {
            if ($ipRange->contains($ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \Generator<AddressInterface>
     */
    public function getIPs(): \Generator
    {
        foreach ($this->ipRanges as $ipRange) {
            yield from $this->getIpsFromRange($ipRange);
        }
    }

    /**
     * @return \Generator<AddressInterface>
     */
    protected function getIpsFromRange(RangeInterface $range): \Generator
    {
        for ($x = 0; $x < $range->getSize(); ++$x) {
            $ip = $range->getAddressAtOffset($x);
            if ($ip) {
                yield $ip;
            }
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

        $range = IPLibFactory::parseRangeString($line);
        if ($range) {
            $this->ipRanges[] = $range;
        }
    }
}
