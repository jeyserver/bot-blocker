<?php

namespace Jeyserver\BotBlocker;

use dnj\Filesystem\Local\File;
use dnj\Log\Logger;
use Exception;
use Illuminate\Container\Container;
use Inotify\InotifyProxy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

$container = Container::getInstance();

$container->singleton(InotifyProxy::class);
$container->singleton(LogAnalyzer::class);
$container->singleton(LogsWatcher::class);
$container->singleton(NginxBlocker::class);
$container->singleton(CsfFirewall::class);
$container->singleton(PhpFpmMonitor::class);
$container->singleton(WhitelistManager::class);
$container->singleton(SelfIPsList::class);
$container->singleton(CsfAllowList::class);
$container->singleton(Logger::class);
$container->singleton(LoggerInterface::class, Logger::class);
$container->singleton(GoogleBotDetector::class, function (Container $container): GoogleBotDetector {
    /** @var LoggerInterface */
    $logger = $container->make(LoggerInterface::class);
    /** @var Config */
    $config = $container->make(Config::class);

    /** @var array<string,array{path:string,url:string}> */
    $files = $config->get(GoogleBotDetector::class.'.options.files');

    return new GoogleBotDetector($logger, $config, $files ?: []);
});
$container->singleton(Rules\GoogleBotRule::class);
$container->singleton(Rules\ServerErrorRule::class);
$container->singleton(Rules\StaticFileRule::class);
$container->singleton(Rules\WhiteListedRule::class);
$container->singleton(Rules\AlreadyBlockedRule::class);
$container->singleton(Rules\BadBotsRule::class, function (Container $container): Rules\BadBotsRule {
    /** @var LoggerInterface */
    $logger = $container->make(LoggerInterface::class);
    /** @var Config */
    $config = $container->make(Config::class);
    $filePath = $config->getFilePath(Rules\BadBotsRule::class.'.options.list');
    if (!$filePath) {
        throw new Exception('Cannot find bad bots list');
    }
    $list = new File($filePath);

    return new Rules\BadBotsRule($logger, $list);
});
$container->singleton(Rules\BruteForceRule::class, function (Container $container): Rules\BruteForceRule {
    /** @var Config */
    $config = $container->make(Config::class);

    /** @var array{maxRequests?:int,period?:int} */
    $options = $config->getOptionsFor(Rules\BruteForceRule::class);

    return new Rules\BruteForceRule($options['maxRequests'] ?? 100, $options['period'] ?? 120);
});
$container->singleton(Rules\WPBruteForceRule::class, function (Container $container): Rules\WPBruteForceRule {
    /** @var Config */
    $config = $container->make(Config::class);
    /** @var array{maxRequests?:int,period?:int} */
    $options = $config->getOptionsFor(Rules\WPBruteForceRule::class);

    return new Rules\WPBruteForceRule($options['maxRequests'] ?? 10, $options['period'] ?? 60);
});
$container->singleton(Application::class, function (Container $container): Application {
    $app = new Application();
    $app->add(new Commands\Start($container));
    $app->add(new Commands\Install($container));

    return $app;
});

$container->singleton('bin-path', function (): string {
    $pharPath = \Phar::running(false);
    if ($pharPath) {
        return $pharPath;
    }

    return dirname(__DIR__).'/bin/bot-blocker';
});

$container->singleton('bin-dir-path', function (): string {
    $pharPath = \Phar::running(false);
    if ($pharPath) {
        return dirname($pharPath);
    }

    return dirname(__DIR__);
});

return $container;
