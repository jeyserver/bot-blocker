<?php

namespace Jeyserver\BotBlocker;

use Generator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class LogAnalyzer implements LoggerAwareInterface
{
    protected LogsWatcher $watcher;
    protected IDefenseSystem $defenseSystem;
    protected ?IMonitorSystem $monitorSystem;
    protected RulesQueue $rules;
    protected LoggerInterface $logger;
    protected int $blockDuration;

    public function __construct(
        LogsWatcher $watcher,
        IDefenseSystem $defenseSystem,
        ?IMonitorSystem $monitorSystem,
        RulesQueue $rules,
        LoggerInterface $logger,
        int $blockDuration = 3600
    ) {
        $this->watcher = $watcher;
        $this->defenseSystem = $defenseSystem;
        $this->monitorSystem = $monitorSystem;
        $this->rules = $rules;
        $this->logger = $logger;
        $this->blockDuration = $blockDuration;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return Generator<null>
     */
    public function read(): Generator
    {
        foreach ($this->watcher->read() as $event) {
            $this->processLogEvent($event);
            yield;
        }
    }

    public function getWatcher(): LogsWatcher
    {
        return $this->watcher;
    }

    public function getDefenseSystem(): IDefenseSystem
    {
        return $this->defenseSystem;
    }

    public function getRules(): RulesQueue
    {
        return $this->rules;
    }

    public function getBlockDuration(): int
    {
        return $this->blockDuration;
    }

    protected function processLogEvent(LogEvent $event): void
    {
        $entry = $event->getEntry();
        $score = 0;
        $rulesScores = [];
        $this->rules->rewind();
        foreach ($this->rules as $rule) {
            $class = get_class($rule);
            $ruleScore = $rule->check($entry);
            $this->logger->debug("Check with '{$class}': {$ruleScore}");
            if (0 == $ruleScore) {
                continue;
            }
            $rulesScores[$class] = $ruleScore;
            $score += $ruleScore;
            if (abs($score) >= 1) {
                break;
            }
        }
        $this->logger->debug("Total score: {$score}");
        if ($score >= 1) {
            $this->logger->notice('Block', ['entry' => $entry, 'reason' => $rulesScores]);
            $ip = $entry->getRemoteHost();
            if (null !== $ip) {
                $this->defenseSystem->block($ip, time() + $this->blockDuration);
            }
        } elseif ($score <= -1) {
            $this->logger->info('Skip beacause', ['entry' => $entry, 'reason' => $rulesScores]);
        }
        if ($this->monitorSystem) {
            $this->monitorSystem->processEntry($entry);
        }
    }
}
