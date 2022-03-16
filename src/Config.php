<?php

namespace Arad\BotBlocker;

use dnj\Filesystem\Local\File;
use dnj\Log\Logger;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use webignition\PathResolver\PathResolver;

class Config
{
    public static function parseFromFile(File $file): self
    {
        $config = json_decode($file->read(), true, 512, JSON_THROW_ON_ERROR);
        self::setClassOptions($config, 'defense-system');
        self::setClassOptions($config, 'monitor-system');
        if (isset($config['rules']) and is_array($config['rules'])) {
            foreach (array_keys($config['rules']) as $key) {
                self::setClassOptions($config, "rules.{$key}");
            }
        }
        $obj = new self($file, $config);

        return $obj;
    }

    /**
     * @param array<string,mixed> $config
     */
    protected static function setClassOptions(array &$config, string $key): void
    {
        $class = Arr::get($config, $key);
        if (null === $class) {
            return;
        }
        $class = self::getOptionsForClassName($class);
        Arr::set($config, $key, $class['name']);
        foreach ($class['options'] as $key => $value) {
            Arr::set($config, $class['name'].".options.{$key}", $value);
        }
    }

    /**
     * @param mixed $class
     *
     * @return array{name:class-string,options:array<string,mixed>}
     */
    protected static function getOptionsForClassName($class): array
    {
        if (is_string($class)) {
            $class = [
                'name' => $class,
            ];
        }
        if (!is_array($class)) {
            throw new Exception('only string or array are accepted as class');
        }
        if (!isset($class['name'])) {
            throw new Exception('Cannot find class name');
        }
        if (!is_string($class['name'])) {
            throw new Exception('Class name must be string');
        }
        if (!class_exists($class['name'])) {
            throw new Exception("Cannot find '{$class['name']}'");
        }
        if (!isset($class['options'])) {
            $class['options'] = [];
        }
        if (!is_array($class['options'])) {
            throw new Exception('options must be array<string,mixed>');
        }

        /**
         * @var array{name:class-string,options:array<string,mixed>} $class
         */
        return $class;
    }

    /**
     * @var array<string,mixed>
     */
    protected array $config;

    protected File $file;

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(File $file, array $config)
    {
        $this->file = $file;
        $this->config = $config;
    }

    public function getConfigFile(): File
    {
        return $this->file;
    }

    public function resolveFilePath(string $path): string
    {
        return (new PathResolver())->resolve($this->file->getDirectory()->getPath(), $path);
    }

    public function getFilePath(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key);
        if (null === $value) {
            return $default;
        }
        if (!is_string($value)) {
            throw new Exception("'{$key}' value is not a string");
        }

        return $this->resolveFilePath($value);
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        Arr::set($this->config, $key, $value);
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptionsFor(string $class): array
    {
        /**
         * @var array<string,mixed>
         */
        $value = Arr::get($this->config, $class.'.options', []);

        return $value;
    }

    public function apply(Container $app): void
    {
        $this->applyLogging($app);
        $this->applyDefenseSystem($app);
        $this->applyMonitorSystem($app);
        $this->applyRules($app);
    }

    protected function applyDefenseSystem(Container $app): void
    {
        /**
         * @var class-string
         */
        $defenseSystem = $this->get('defense-system');
        if ($defenseSystem) {
            $app->singleton(IDefenseSystem::class, $defenseSystem);
        }
    }

    protected function applyMonitorSystem(Container $app): void
    {
        /**
         * @var class-string
         */
        $monitorSystem = $this->get('monitor-system');
        if ($monitorSystem) {
            $app->singleton(IMonitorSystem::class, $monitorSystem);
        }
    }

    protected function applyRules(Container $app): void
    {
        $rules = $this->get('rules');
        if (!$rules) {
            return;
        }
        if (!is_array($rules)) {
            throw new Exception("'rules' is not array");
        }
        $queue = new RulesQueue();
        foreach ($rules as $rule) {
            /**
             * @var IRule
             */
            $rule = $app->make($rule);
            $queue->enqueue($rule);
        }
        $app->instance(RulesQueue::class, $queue);
    }

    protected function applyLogging(Container $app): void
    {
        $quiet = $this->get('logging.quiet');

        /**
         * @var Logger
         */
        $logger = $app->make(Logger::class);
        if (null !== $quiet) {
            $logger->setQuiet(boolval($quiet));
        }

        $level = $this->get('logging.level');
        if (null !== $level) {
            $logger->setLevel($level);
        }

        /**
         * @var string|null
         */
        $file = $this->get('logging.file');
        if (null !== $file) {
            $file = $this->resolveFilePath($file);
            $logger->setFile(new File($file));
        }
    }
}
