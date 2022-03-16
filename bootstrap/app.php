<?php

namespace Arad\BotBlocker;

use dnj\Filesystem\Local\File;
use dnj\Log\Logger;
use Exception;
use Illuminate\Container\Container;
use Inotify\InotifyProxy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

$container = Container::getInstance();

$container->singleton(InotifyProxy::class);
$container->singleton(GoogleBotDetector::class);
$container->singleton(LogAnalyzer::class);
$container->singleton(LogsWatcher::class);
$container->singleton(NginxBlocker::class);
$container->singleton(PhpFpmMonitor::class);
$container->singleton(WhitelistManager::class);
$container->singleton(SelfIPsList::class);
$container->singleton(CsfAllowList::class);
$container->singleton(Rules\GoogleBotRule::class);
$container->singleton(Rules\ServerErrorRule::class);
$container->singleton(Rules\StaticFileRule::class);
$container->singleton(Rules\WhiteListedRule::class);
$container->singleton(Rules\AlreadyBlockedRule::class);
$container->singleton(Logger::class);
$container->singleton(LoggerInterface::class, Logger::class);
$container->singleton(Rules\BadBotsRule::class, function ($container) {
    $logger = $container->make(LoggerInterface::class);
    $config = $container->make(Config::class);
    $filePath = $config->getFilePath(Rules\BadBotsRule::class.'.options.list');
    if (!$filePath) {
        throw new Exception('Cannot find bad bots list');
    }
    $list = new File($filePath);

    return new Rules\BadBotsRule($logger, $list);
});
$container->singleton(Rules\BruteForceRule::class, function ($container) {
    $config = $container->make(Config::class);
    $options = $config->getOptionsFor(Rules\BruteForceRule::class);

    return new Rules\BruteForceRule($options['maxRequests'] ?? 100, $options['period'] ?? 120);
});
$container->singleton(Application::class, function ($container) {
    $app = new Application();
    $app->add(new Commands\Start($container));
    $app->add(new Commands\Install());

    return $app;
});

$container->singleton('bin-path', function () {
    $pharPath = \Phar::running(false);
    if ($pharPath) {
        return $pharPath;
    }

    return dirname(__DIR__).'/bin/bot-blocker';
});

$container->singleton('bin-dir-path', function () {
    $pharPath = \Phar::running(false);
    if ($pharPath) {
        return dirname($pharPath);
    }

    return dirname(__DIR__);
});

return $container;
