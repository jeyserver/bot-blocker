<?php

namespace Arad\BotBlocker;

use dnj\Filesystem\Local;
use dnj\Filesystem\Tmp;
use Exception;
use GuzzleHttp\Client;
use IPLib\Factory as IPLibFactory;
use IPLib\Range\RangeInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class GoogleBotDetector implements LoggerAwareInterface
{
    protected LoggerInterface $logger;
    protected Config $config;

    /**
     * @var array<string,array{path:string,url:string}>
     */
    protected array $files = [];

    /**
     * @var RangeInterface[]
     */
    protected array $ipRanges = [];

    protected bool $isFullyLoaded = true;

    /**
     * @param array<string,array{path:string,url:string}> $files
     */
    public function __construct(LoggerInterface $logger, Config $config, array $files = [])
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->files = $files ?: $this->getDefaultFiles();
        try {
            $this->update();
        } catch (Exception $e) {
            $this->logger->error('error during update: '.$e->__toString());
        }
        $this->reload();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function isGoogleBot(LogEntry $entry): bool
    {
        $ip = $entry->getRemoteHost();
        if ($this->isFullyLoaded and $ip and $this->ipRanges) {
            foreach ($this->ipRanges as $range) {
                if ($range->contains($ip)) {
                    return true;
                }
            }

            return false;
        }

        $agent = $entry->getUserAgent();
        if ($agent and false !== stripos($agent, 'googlebot')) {
            return true;
        }

        return false;
    }

    /**
     * @return RangeInterface[]
     */
    public function getIpRanges(): array
    {
        return $this->ipRanges;
    }

    public function reload(): void
    {
        foreach ($this->files as $name => $data) {
            $file = new Local\File(
                $this->config->resolveFilePath($data['path'])
            );

            /** @var array{prefixes:array<array{ipv6Prefix?:string,ipv4Prefix?:string}>}|false|null */
            $json = json_decode($file->read(), true);
            if (!$json and JSON_ERROR_NONE != json_last_error()) {
                $this->logger->error('could not load google bot ip', ['path' => $data['path']]);
                $this->isFullyLoaded = false;
                continue;
            }

            $prefixes = $json['prefixes'] ?? [];

            foreach ($prefixes as $ipPrefixes) {
                foreach ($ipPrefixes as $ipPrefix) {
                    $range = IPLibFactory::parseRangeString($ipPrefix);
                    if ($range) {
                        $this->ipRanges[] = $range;
                    }
                }
            }
        }
    }

    /**
     * @return bool whether it's need to be reloaded or not
     */
    public function update(): bool
    {
        $changed = false;

        foreach ($this->files as $name => $data) {
            $this->logger->info('download', ['url' => $data['url']]);
            $tmp = $this->download($data['url']);

            $file = new Local\File($this->config->resolveFilePath($data['path']));

            if ($file->exists()) {
                $oldMd5 = $file->md5();
                $newMd5 = $tmp->md5();
                if ($oldMd5 == $newMd5) {
                    continue;
                }
            } else {
                $dir = $file->getDirectory();
                if (!$dir->exists()) {
                    $dir->make();
                }
            }
            $changed = true;
            $tmp->copyTo($file);
        }

        return $changed;
    }

    /**
     * @return array<string,array{path:string,url:string}>
     */
    protected function getDefaultFiles(): array
    {
        $pathPrefix = 'google-bot-detector';

        return [
            'goog.json' => [
                'path' => "{$pathPrefix}/goog.json",
                'url' => 'https://www.gstatic.com/ipranges/goog.json',
            ],
            'googlebot.json' => [
                'path' => "{$pathPrefix}/googlebot.json",
                'url' => 'https://developers.google.com/static/search/apis/ipranges/googlebot.json',
            ],
            'special-crawlers.json' => [
                'path' => "{$pathPrefix}/special-crawlers.json",
                'url' => 'https://developers.google.com/static/search/apis/ipranges/special-crawlers.json',
            ],
            'user-triggered-fetchers.json' => [
                'path' => "{$pathPrefix}/user-triggered-fetchers.json",
                'url' => 'https://developers.google.com/static/search/apis/ipranges/user-triggered-fetchers.json',
            ],
        ];
    }

    protected function download(string $url): Tmp\File
    {
        $client = new Client();
        $response = $client->get($url, ['timeout' => 5]);
        $tmp = new Tmp\File();
        $tmp->write($response->getBody()->getContents());

        return $tmp;
    }
}
