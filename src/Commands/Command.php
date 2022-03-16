<?php

namespace Arad\BotBlocker\Commands;

use Arad\BotBlocker\Config;
use dnj\Filesystem\Local;
use Exception;
use Illuminate\Container\Container;
use Symfony\Component\Console\Command\Command as ParentCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends ParentCommand
{
    protected Container $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    protected function configure(): void
    {
        $dir = $this->app->make('bin-dir-path');
        $this->addOption('--log-level', null, InputArgument::OPTIONAL, 'How much logs must be generated.', null);
        $this->addOption('--config', 'c', InputArgument::OPTIONAL, 'Path to config.json.', $dir.'/config.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = $input->getOption('config');
        if (!is_string($configPath)) {
            throw new Exception('config is not string');
        }
        $isQuiet = $input->hasParameterOption(['--quiet', '-q'], true);
        $verbosity = $input->getOption('log-level');
        if (!is_null($verbosity) and !is_string($verbosity)) {
            throw new Exception('log-level is not string');
        }

        $configFile = new Local\File($configPath);
        $config = Config::parseFromFile($configFile);
        $this->app->instance(Config::class, $config);

        $config->set('logging.quiet', $isQuiet);
        if (!is_null($verbosity)) {
            $config->set('logging.level', $verbosity);
        }
        $config->apply($this->app);

        return Command::SUCCESS;
    }
}
