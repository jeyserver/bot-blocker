<?php

namespace Arad\BotBlocker;

use Kassner\LogParser\LogParser;

class LogEntry implements \JsonSerializable
{
    protected LogFile $file;
    protected ?int $status = null;
    protected ?string $remoteHost = null;
    protected ?string $user = null;
    protected ?int $time = null;
    protected ?string $requestMethod = null;
    protected ?string $path = null;

    /**
     * @var array<string,mixed>|null
     */
    protected ?array $queries = [];
    protected ?int $responseBytes = null;
    protected ?string $scheme = null;
    protected ?string $serverName = null;
    protected ?string $userAgent = null;
    protected ?string $referer = null;

    public function __construct(LogFile $file, string $line)
    {
        $this->file = $file;
        $this->parse($line);
    }

    public function getFile(): LogFile
    {
        return $this->file;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getRemoteHost(): ?string
    {
        return $this->remoteHost;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getQueries(): ?array
    {
        return $this->queries;
    }

    public function getResponseBytes(): ?int
    {
        return $this->responseBytes;
    }

    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    /**
     * @return array{status?:int,remoteHost?:string,user?:string,time?:int,requestMethod?:string,path?:string,queries?:string,responseBytes?:int,scheme?:string,serverName?:string,userAgent?:string,referer?:string}
     */
    public function jsonSerialize(): array
    {
        $json = [];
        foreach (['status', 'remoteHost', 'user', 'time', 'requestMethod', 'path', 'queries', 'responseBytes', 'scheme', 'serverName', 'userAgent', 'referer'] as $key) {
            if (isset($this->{$key})) {
                $json[$key] = $this->{$key};
            }
        }

        return $json;
    }

    protected function parse(string $line): void
    {
        $parser = new LogParser();
        $parser->setFormat('%h %l %u %t "%r" %>s %O "%{Referer}i" \"%{User-Agent}i"');
        $entry = $parser->parse($line);
        if (isset($entry->status)) {
            $this->status = intval($entry->status);
        }
        if (isset($entry->host)) {
            $this->remoteHost = $this->makeItNull($entry->host);
        }
        if (isset($entry->user)) {
            $this->user = $this->makeItNull($entry->user);
        }
        if (isset($entry->stamp)) {
            $this->time = $entry->stamp;
        }
        if (isset($entry->sentBytes)) {
            $this->responseBytes = intval($this->makeItNull($entry->sentBytes));
        }
        if (isset($entry->scheme)) {
            $this->scheme = $this->makeItNull($entry->scheme);
        }
        if (isset($entry->serverName)) {
            $this->serverName = $this->makeItNull($entry->serverName);
        }
        if (isset($entry->HeaderUserAgent)) {
            $this->userAgent = $this->makeItNull($entry->HeaderUserAgent);
        }
        if (isset($entry->HeaderReferer)) {
            $this->referer = $this->makeItNull($entry->HeaderReferer);
        }
        if (isset($entry->request)) {
            $this->parseRequest($entry->request);
        }
    }

    protected function parseRequest(string $request): void
    {
        if (!preg_match("/^(\w+)\s(.*)\s/i", $request, $matches)) {
            return;
        }
        if (null === $this->requestMethod) {
            $this->requestMethod = strtoupper($matches[1]);
        }
        $this->parseURI($matches[2]);
    }

    protected function parseURI(string $uri): void
    {
        $questionMarkPos = strpos($uri, '?');
        if (false === $questionMarkPos) {
            $this->path = $uri;
            $this->queries = [];
        } else {
            $this->path = substr($uri, 0, $questionMarkPos);
            parse_str(substr($uri, $questionMarkPos + 1), $this->queries);
        }
    }

    protected function makeItNull(string $str): ?string
    {
        $str = trim($str);
        if (empty($str) or '-' == $str) {
            return null;
        }

        return $str;
    }
}
