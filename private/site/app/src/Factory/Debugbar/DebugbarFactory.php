<?php

declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

namespace App\Factory\Debugbar;

use App\Factory\Db\DbInterface;
use App\Factory\Logger\LoggerInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use DebugBar\Bridge\MonologCollector;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\StandardDebugBar;
use Frugan\DebugbarRedbean\RedBeanCollector;
use Psr\Container\ContainerInterface;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class DebugbarFactory extends Model implements DebugbarInterface, \ArrayAccess
{
    protected ?StandardDebugBar $instance = null;
    protected bool $enabled = false;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected DbInterface $db,
        protected HelperInterface $helper,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    #[\Override]
    public function __get($name)
    {
        // Intercept any access to 'config' property to prevent conflicts
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }

        // Delegate to ArrayAccess for consistency with other properties
        return $this->offsetGet($name);
    }

    public function getInstance(): ?StandardDebugBar
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        // Check if debugbar should be enabled
        $this->enabled = $this->shouldEnable();

        if (!$this->enabled) {
            return $this; // Don't create instance if not enabled
        }

        try {
            $this->instance = new StandardDebugBar();

            // Add Config Collector
            $this->instance->addCollector(new ConfigCollector($this->config->all()));

            // Add Monolog Collectors
            $this->instance->addCollector(new MonologCollector($this->logger->channel()));
            $this->instance->addCollector(new MonologCollector($this->logger->channel('internal'), name: 'monolog internal'));

            // Add Database Collector if enabled
            if ($this->config->get('db.debug.enabled')) {
                $this->instance->addCollector(new RedBeanCollector($this->db));
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to create Debugbar instance: '.$e->getMessage());
            $this->enabled = false;
        }

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function addMessage(string $message, string $level = 'info'): self
    {
        if ($this->instance && $this->enabled) {
            // Use ArrayAccess to get the messages collector
            if ($this->instance->hasCollector('messages')) {
                $this->instance['messages']->addMessage($message, $level);
            }
        }

        return $this;
    }

    public function getCollector(string $name): mixed
    {
        if ($this->instance && $this->enabled && $this->instance->hasCollector($name)) {
            return $this->instance[$name];
        }

        return $this->createMockCollector();
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        if (null === $this->instance || !$this->enabled) {
            return true; // Always return true for mock behavior
        }

        return $this->instance->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (null === $this->instance || !$this->enabled) {
            return $this->createMockCollector();
        }

        return $this->instance->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (null !== $this->instance && $this->enabled) {
            $this->instance->offsetSet($offset, $value);
        }
        // Do nothing if not enabled (mock behavior)
    }

    public function offsetUnset(mixed $offset): void
    {
        if (null !== $this->instance && $this->enabled) {
            $this->instance->offsetUnset($offset);
        }
        // Do nothing if not enabled (mock behavior)
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    protected function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "debug.{$env}",
            'debug',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    /**
     * Return a mock collector for non-enabled debugbar.
     */
    private function createMockCollector(): object
    {
        return new class {
            public function addMessage($message, $level = 'info')
            {
                return $this;
            }

            public function __call($name, $arguments)
            {
                return $this;
            }

            public function __get($name)
            {
                return $this;
            }
        };
    }

    private function shouldEnable(): bool
    {
        $env = $this->container->get('env');

        return $this->getConfigWithFallback('enabled')
            && $this->getConfigWithFallback('debugbar.enabled')
            && !$this->helper->Env()->isCli();
    }
}
