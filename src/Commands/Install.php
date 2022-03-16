<?php

namespace Arad\BotBlocker\Commands;

use dnj\Filesystem\Local;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Install extends Command
{
    protected static $defaultName = 'install';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $error = Command::SUCCESS != $this->installService($input, $output);
        $error = ($error and Command::SUCCESS != $this->installLogRotation($input, $output));

        return $error ? Command::FAILURE : Command::SUCCESS;
    }

    protected function installService(InputInterface $input, OutputInterface $output): int
    {
        $serviceFile = new Local\File('/etc/systemd/system/bot-blocker.service');
        if ($serviceFile->exists()) {
            $output->writeln("Service 'bot-blocker.service' is already installed");

            return Command::FAILURE;
        }

        $pathToBotBlocker = __DIR__.'/bot-blocker';
        $phpBin = PHP_BINARY;
        $content = <<<EOF
[Service]
Type=simple
ExecStart={$phpBin} -d disable_functions {$pathToBotBlocker} start -q

[Install]
WantedBy=multi-user.target
EOF;

        $serviceFile->write($content);
        shell_exec('systemctl enable bot-blocker');
        $output->writeln("To start the start run:\n\tsystemctl start bot-blocker");

        return Command::SUCCESS;
    }

    protected function installLogRotation(InputInterface $input, OutputInterface $output): int
    {
        $config = new Local\File('/etc/logrotate.d/bot-blocker');
        if ($config->exists()) {
            $output->writeln('Log rotation for bot-blocker is already configured');

            return Command::FAILURE;
        }

        $content = <<<EOF
/var/log/bot-blocker.log {
    daily
    rotate 7
    compress
}
EOF;
        $config->write($content);

        return Command::SUCCESS;
    }
}
