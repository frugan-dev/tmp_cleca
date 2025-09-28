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

namespace App\Factory\Mailer\Provider;

use App\Config\ConfigArrayWrapper;
use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderInterface;
use Illuminate\Config\Repository;
use Psr\Container\ContainerInterface;

class ProviderRegistry
{
    private array $providers = [];
    private array $instances = [];

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger
    ) {}

    /**
     * Register a provider class.
     */
    public function register(string $name, string $className, array $config = []): void
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException("Provider class does not exist: {$className}");
        }

        if (!is_subclass_of($className, TokenProviderInterface::class)) {
            throw new \InvalidArgumentException("Provider must implement TokenProviderInterface: {$className}");
        }

        $this->providers[$name] = [
            'class' => $className,
            'config' => $config,
        ];

        $this->logger->debugInternal("Registered OAuth2 provider: {$name}", [
            'class' => $className,
            'config_keys' => array_keys($config),
        ]);
    }

    /**
     * Get a provider instance.
     */
    public function get(string $name): TokenProviderInterface
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException("Provider not registered: {$name}");
        }

        // Return cached instance
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Create new instance
        $providerConfig = $this->providers[$name];
        $className = $providerConfig['class'];

        $this->logger->debugInternal("Creating OAuth2 provider instance: {$name}");

        // Use container to resolve dependencies
        if ($this->container->has($className)) {
            $instance = $this->container->get($className);
        } else {
            // Fallback: manual instantiation (requires constructor compatibility)
            $instance = new $className($this->container, $this->logger);
        }

        if (!$instance instanceof TokenProviderInterface) {
            throw new \RuntimeException("Provider instance must implement TokenProviderInterface: {$className}");
        }

        // Cache instance
        $this->instances[$name] = $instance;

        return $instance;
    }

    /**
     * Check if provider is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->providers[$name]);
    }

    /**
     * Get all available provider names.
     */
    public function getAvailable(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Get provider config.
     */
    public function getConfig(string $name): array
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException("Provider not registered: {$name}");
        }

        return $this->providers[$name]['config'];
    }

    /**
     * Auto-register providers from config.
     */
    public function autoRegister(ConfigArrayWrapper|Repository $config): void
    {
        try {
            $providers = $config->get('mail.oauth2.providers', []);

            if (empty($providers)) {
                $this->logger->warning('No OAuth2 providers configured in mail.oauth2.providers');

                return;
            }

            foreach ($providers as $name => $settings) {
                if (!isset($settings['class'])) {
                    $this->logger->warning('Skipping provider registration - missing class', [
                        'provider' => $name,
                    ]);

                    continue;
                }

                $this->register(
                    $name,
                    $settings['class'],
                    $settings['config'] ?? []
                );
            }

            $this->logger->debugInternal('ProviderRegistry auto-registration completed', [
                'available_providers' => $this->getAvailable(),
                'stats' => $this->getStats(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to auto-register OAuth2 providers', [
                'exception' => $e,
                'error' => $e->getMessage(),
                'text' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Find provider by support check.
     */
    public function findByProvider(string $providerName): ?TokenProviderInterface
    {
        foreach ($this->getAvailable() as $registeredName) {
            try {
                $provider = $this->get($registeredName);
                if ($provider->supports($providerName)) {
                    return $provider;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Error checking provider support', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                    'provider' => $providerName,
                    'registered_name' => $registeredName,
                ]);
            }
        }

        return null;
    }

    /**
     * Get provider statistics.
     */
    public function getStats(): array
    {
        return [
            'registered' => \count($this->providers),
            'instantiated' => \count($this->instances),
            'providers' => array_map(fn ($p) => [
                'class' => $p['class'],
                'config_count' => \count($p['config']),
            ], $this->providers),
        ];
    }
}
