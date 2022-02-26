<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;
use dnj\Filesystem\Exceptions\NotFoundException;
use dnj\Filesystem\Local;
use dnj\Filesystem\Tmp;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class BadBotsRule implements IRule, LoggerAwareInterface
{
    protected LoggerInterface $logger;
    protected Local\File $file;

    /**
     * @var string[]
     */
    protected array $bads = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->file = new Local\File(__DIR__.'/../../assets/bad-user-agents.list');
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

    public function reload(): void
    {
        if (!$this->file->exists()) {
            throw new NotFoundException($this->file);
        }

        $this->bads = [];
        $bads = explode("\n", $this->file->read());
        foreach ($bads as $bad) {
            $bad = trim($bad);
            if (empty($bad)) {
                continue;
            }
            $bad = str_replace('\\ ', ' ', $bad);
            $bad = preg_quote($bad, '/');
            $this->bads[] = $bad;
        }
    }

    /**
     * @return bool whether it's need to be reloaded or not
     */
    public function update(): bool
    {
        $url = 'https://github.com/mitchellkrogza/nginx-ultimate-bad-bot-blocker/raw/master/_generator_lists/bad-user-agents.list';
        $this->logger->info('download', ['url' => $url]);
        $tmp = $this->download($url);
        $newMd5 = $tmp->md5();
        if ($this->file->exists()) {
            $oldMd5 = $this->file->md5();
            if ($oldMd5 == $newMd5) {
                return false;
            }
        } else {
            $dir = $this->file->getDirectory();
            if (!$dir->exists()) {
                $dir->make();
            }
        }
        $tmp->copyTo($this->file);

        return true;
    }

    public function check(LogEntry $entry): float
    {
        $agent = $entry->getUserAgent();
        if (null === $agent) {
            return 0;
        }
        foreach ($this->bads as $bad) {
            if (preg_match("/(?:\\b){$bad}(?:\\b)/i", $agent, $matches)) {
                $this->logger->debug("user-agent contains '{$matches[0]}' keyword");

                return 1;
            }
        }

        return 0;
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
