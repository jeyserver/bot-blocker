<?php

namespace Arad\BotBlocker\Rules;

use Arad\BotBlocker\IRule;
use Arad\BotBlocker\LogEntry;
use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class StaticFileRule implements IRule
{
    /**
     * @var string[]
     */
    protected array $extensions;

    public function __construct()
    {
        /**
         * @var string[]
         */
        $extensions = array_keys(GeneratedExtensionToMimeTypeMap::MIME_TYPES_FOR_EXTENSIONS);
        $this->extensions = $extensions;
    }

    public function check(LogEntry $entry): float
    {
        $path = $entry->getPath();
        if (null === $path) {
            return 0;
        }
        if (!preg_match("/\.([\w\d]+)$/", $path, $matches)) {
            return 0;
        }
        if ('php' == substr($matches[1], 0, 3)) {
            return 0;
        }
        if (!in_array($matches[1], $this->extensions)) {
            return 0;
        }

        return -1;
    }
}
