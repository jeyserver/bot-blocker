<?php

namespace Arad\BotBlocker;

use dnj\Filesystem\Local\File;
use dnj\Log\Logger;
use Illuminate\Container\Container;
use Inotify\InotifyProxy;
use Psr\Log\LogLevel;

$container = Container::getInstance();

$container->singleton(IDefenseSystem::class, NginxBlocker::class);
$container->singleton(InotifyProxy::class);
$container->singleton(GoogleBotDetector::class);
$container->singleton(LogAnalyzer::class);
$container->singleton(LogsWatcher::class);
$container->singleton(NginxBlocker::class);
$container->singleton(WhitelistManager::class);
$container->singleton(CsfAllowList::class);
$container->singleton(Rules\GoogleBotRule::class);
$container->singleton(Rules\ServerErrorRule::class);
$container->singleton(Rules\StaticFileRule::class);
$container->singleton(Rules\WhiteListedRule::class);
$container->singleton(Rules\AlreadyBlockedRule::class);
$container->singleton(Rules\BruteForceRule::class, function () {
    return new Rules\BruteForceRule(100, 120);
});
$container->singleton(Logger::class, function () {
    $logger = new Logger();
    $logger->setQuiet(true);
    $logger->setLevel(LogLevel::NOTICE);
    $logger->setFile(new File('/var/log/bot-blocker.log'));

    return $logger;
});
$container->bind(\Psr\Log\LoggerInterface::class, function ($container) {
    return $container->make(Logger::class)->getInstance();
});

$container->singleton(RulesQueue::class, function ($container) {
    $rules = new RulesQueue();
    $rules->enqueue($container->make(Rules\AlreadyBlockedRule::class));
    $rules->enqueue($container->make(Rules\WhiteListedRule::class));
    $rules->enqueue($container->make(Rules\GoogleBotRule::class));
    $rules->enqueue($container->make(Rules\StaticFileRule::class));
    $rules->enqueue($container->make(Rules\WPAdminRequestRule::class));
    $rules->enqueue($container->make(Rules\ServerErrorRule::class));
    $rules->enqueue($container->make(Rules\BadBotsRule::class));
    $rules->enqueue($container->make(Rules\BruteForceRule::class));

    return $rules;
});

return $container;
