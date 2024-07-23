<?php

namespace Jeyserver\BotBlocker\Commands;

use Jeyserver\BotBlocker\LogAnalyzer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Start extends Command
{
    protected static $defaultName = 'start';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        /**
         * @var LogAnalyzer
         */
        $analyzer = $this->app->make(LogAnalyzer::class);
        $analyzer->getWatcher()->start();
        $process = $analyzer->read();
        while ($process->valid()) {
            $process->next();
            $analyzer->getDefenseSystem()->clear();
        }

        return Command::SUCCESS;
    }
}
