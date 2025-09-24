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

namespace App\Factory\Mailer\OAuth2\TokenProvider;

use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\Provider\ProviderRegistry;
use App\Model\Model;
use Psr\Container\ContainerInterface;

/**
 * Enhanced Factory class with improved fallback handling.
 */
class TokenProviderFactory extends Model
{
    private const int HEALTH_CACHE_TTL = 300; // 5 minutes
    private const int HEALTH_CACHE_FAILED_TTL = 60; // 1 minute for failed providers
    private bool $initialized = false;
    private array $healthStatus = []; // Track provider health
    private array $healthCheckedAt = []; // Track when health was last checked

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected ProviderRegistry $registry
    ) {}

    /**
     * Create a token provider by name with health checking.
     */
    public function create(string $providerName, array $config = []): TokenProviderInterface
    {
        $this->ensureInitialized();

        $this->logger->debugInternal("Creating OAuth2 token provider: {$providerName}");

        try {
            $provider = $this->resolveProvider($providerName);

            if (!$provider) {
                throw new \InvalidArgumentException("Unsupported OAuth2 provider: {$providerName}");
            }

            // Update health status
            $this->updateProviderHealth($providerName, true);

            return $provider;
        } catch (\Exception $e) {
            // Mark as unhealthy
            $this->updateProviderHealth($providerName, false);

            $this->logger->error("Failed to create OAuth2 provider: {$providerName}", [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException("Cannot create OAuth2 provider '{$providerName}': ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Create provider using intelligent fallback logic with health checking.
     */
    public function createWithFallback(array $config = []): TokenProviderInterface
    {
        $this->ensureInitialized();

        // Check if specific provider is configured
        $configuredProvider = $this->container->get('config')->get('mail.oauth2.provider');

        if ($configuredProvider) {
            $this->logger->debugInternal("Using configured OAuth2 provider: {$configuredProvider}");

            try {
                $provider = $this->resolveProvider($configuredProvider);
                if (!$provider) {
                    throw new \InvalidArgumentException("Configured OAuth2 provider not found: {$configuredProvider}");
                }

                // Test health if it's AbstractTokenProvider
                if ($provider instanceof AbstractTokenProvider && !$provider->isHealthy()) {
                    throw new \Exception('Configured provider failed health check');
                }

                $this->updateProviderHealth($configuredProvider, true);

                return $provider;
            } catch (\Exception $e) {
                $this->logger->warning('Configured provider failed, falling back to auto-selection', [
                    'configured_provider' => $configuredProvider,
                    'error' => $e->getMessage(),
                ]);

                $this->updateProviderHealth($configuredProvider, false);
                // Continue to fallback logic below
            }
        }

        // First try healthy providers only
        $healthyProviders = $this->getHealthyProviders();

        if (!empty($healthyProviders)) {
            $lastException = null;

            foreach ($healthyProviders as $providerName) {
                try {
                    $this->logger->debugInternal("Attempting healthy OAuth2 provider: {$providerName}");

                    $provider = $this->resolveProvider($providerName);
                    if (!$provider) {
                        throw new \InvalidArgumentException("Unsupported OAuth2 provider: {$providerName}");
                    }

                    // Update health status
                    $this->updateProviderHealth($providerName, true);

                    $this->logger->debugInternal("Successfully using healthy OAuth2 provider: {$providerName}");

                    return $provider;
                } catch (\Exception $e) {
                    $lastException = $e;
                    $this->logger->warning("Healthy provider failed: {$providerName}", [
                        'error' => $e->getMessage(),
                    ]);

                    // Mark as unhealthy
                    $this->updateProviderHealth($providerName, false);

                    continue;
                }
            }

            $this->logger->warning('All healthy providers failed, trying all available providers');
        }

        // Fallback: try all available providers (including previously unhealthy ones)
        $availableProviders = $this->getAvailableProviders();

        if (empty($availableProviders)) {
            throw new \RuntimeException('No OAuth2 providers are configured');
        }

        $lastException = null;

        foreach ($availableProviders as $providerName) {
            // Skip if we already tried this healthy provider above
            if (\in_array($providerName, $healthyProviders, true)) {
                continue;
            }

            try {
                $this->logger->debugInternal("Attempting fallback OAuth2 provider: {$providerName}");

                $provider = $this->resolveProvider($providerName);
                if (!$provider) {
                    throw new \InvalidArgumentException("Unsupported OAuth2 provider: {$providerName}");
                }

                // Test the provider by checking health
                if ($provider instanceof AbstractTokenProvider && !$provider->isHealthy()) {
                    throw new \Exception('Provider health check failed');
                }

                // Update health status
                $this->updateProviderHealth($providerName, true);

                $this->logger->debugInternal("Successfully using fallback OAuth2 provider: {$providerName}");

                return $provider;
            } catch (\Exception $e) {
                $lastException = $e;
                $this->logger->warning("Fallback provider failed: {$providerName}", [
                    'error' => $e->getMessage(),
                ]);

                // Mark as unhealthy
                $this->updateProviderHealth($providerName, false);

                continue;
            }
        }

        throw new \RuntimeException(
            'All OAuth2 providers failed during fallback: '.$lastException?->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * Create provider based on email domain with improved fallback and health checking.
     */
    public function createByEmailDomain(string $email, array $config = []): TokenProviderInterface
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        // Map domain to provider name
        $providerMap = [
            'outlook.com' => 'microsoft-office365',
            'hotmail.com' => 'microsoft-office365',
            'live.com' => 'microsoft-office365',
            'msn.com' => 'microsoft-office365',
            'gmail.com' => 'google-gmail',
            'googlemail.com' => 'google-gmail',
        ];

        $preferredProvider = $providerMap[$domain] ?? null;

        // Try preferred provider first if available and healthy
        if ($preferredProvider && $this->supports($preferredProvider) && $this->isProviderHealthy($preferredProvider)) {
            try {
                $this->logger->debugInternal("Using preferred provider for domain {$domain}: {$preferredProvider}");

                $provider = $this->resolveProvider($preferredProvider);
                if (!$provider) {
                    throw new \InvalidArgumentException("Unsupported OAuth2 provider: {$preferredProvider}");
                }

                // Update health status
                $this->updateProviderHealth($preferredProvider, true);

                return $provider;
            } catch (\Exception $e) {
                $this->logger->debugInternal('Preferred provider failed, using fallback', [
                    'email_domain' => $domain,
                    'preferred_provider' => $preferredProvider,
                    'error' => $e->getMessage(),
                ]);

                // Mark as unhealthy
                $this->updateProviderHealth($preferredProvider, false);
            }
        }

        // Use fallback logic with health checking
        return $this->createWithFallback($config);
    }

    /**
     * Get available providers sorted by health status.
     */
    public function getAvailableProviders(): array
    {
        $this->ensureInitialized();

        return $this->registry->getAvailable();
    }

    /**
     * Get healthy providers only.
     */
    public function getHealthyProviders(): array
    {
        $available = $this->getAvailableProviders();

        return array_filter($available, $this->isProviderHealthy(...));
    }

    /**
     * Check if a provider is supported.
     */
    public function supports(string $providerName): bool
    {
        $this->ensureInitialized();

        return $this->registry->has($providerName) || null !== $this->registry->findByProvider($providerName);
    }

    /**
     * Check and update health status for all providers.
     */
    public function checkProvidersHealth(): array
    {
        $healthReport = [];

        foreach ($this->getAvailableProviders() as $providerName) {
            try {
                $provider = $this->registry->get($providerName);

                if ($provider instanceof AbstractTokenProvider) {
                    $isHealthy = $provider->isHealthy();
                    $this->updateProviderHealth($providerName, $isHealthy);

                    $healthReport[$providerName] = [
                        'healthy' => $isHealthy,
                        'checked_at' => time(),
                    ];
                }
            } catch (\Exception $e) {
                $this->updateProviderHealth($providerName, false);

                $healthReport[$providerName] = [
                    'healthy' => false,
                    'error' => $e->getMessage(),
                    'checked_at' => time(),
                ];
            }
        }

        $this->logger->debugInternal('Provider health check completed', $healthReport);

        return $healthReport;
    }

    /**
     * Check if a provider is currently healthy with caching.
     */
    public function isProviderHealthy(string $providerName): bool
    {
        $now = time();

        // Check in-memory cache first
        if (isset($this->healthStatus[$providerName], $this->healthCheckedAt[$providerName])) {
            $cacheAge = $now - $this->healthCheckedAt[$providerName];
            $isHealthy = $this->healthStatus[$providerName];
            $maxAge = $isHealthy ? self::HEALTH_CACHE_TTL : self::HEALTH_CACHE_FAILED_TTL;

            if ($cacheAge < $maxAge) {
                $this->logger->debugInternal("Using cached health status for factory: {$providerName}", [
                    'healthy' => $isHealthy,
                    'cache_age' => $cacheAge,
                ]);

                return $isHealthy;
            }
        }

        // Cache expired or not available, check provider directly
        try {
            if ($this->supports($providerName)) {
                $provider = $this->resolveProvider($providerName);

                if ($provider instanceof AbstractTokenProvider) {
                    $isHealthy = $provider->isHealthy();
                    $this->updateProviderHealth($providerName, $isHealthy);

                    return $isHealthy;
                }
            }
        } catch (\Exception $e) {
            $this->logger->debugInternal("Health check failed for provider: {$providerName}", [
                'error' => $e->getMessage(),
            ]);

            $this->updateProviderHealth($providerName, false);

            return false;
        }

        // Default to healthy if we can't determine
        return true;
    }

    /**
     * Clear health cache for all providers.
     */
    public function clearHealthCache(): void
    {
        $this->healthStatus = [];
        $this->healthCheckedAt = [];

        // Also clear provider-level caches
        foreach ($this->getAvailableProviders() as $providerName) {
            try {
                $provider = $this->resolveProvider($providerName);
                if ($provider instanceof AbstractTokenProvider) {
                    $provider->clearHealthCache();
                }
            } catch (\Exception $e) {
                $this->logger->debugInternal("Failed to clear health cache for provider: {$providerName}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->debugInternal('Cleared all health caches');
    }

    /**
     * Get health status for all providers.
     */
    public function getHealthStatuses(): array
    {
        $statuses = [];

        foreach ($this->getAvailableProviders() as $providerName) {
            try {
                $provider = $this->resolveProvider($providerName);

                if ($provider instanceof AbstractTokenProvider) {
                    $statuses[$providerName] = $provider->getHealthInfo();
                } else {
                    $statuses[$providerName] = [
                        'provider' => $providerName,
                        'healthy' => null,
                        'note' => 'Not an AbstractTokenProvider',
                    ];
                }
            } catch (\Exception $e) {
                $statuses[$providerName] = [
                    'provider' => $providerName,
                    'healthy' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $statuses;
    }

    /**
     * Resolve provider from various sources.
     */
    private function resolveProvider(string $providerName): ?TokenProviderInterface
    {
        // 1. Try registry first
        if ($this->registry->has($providerName)) {
            return $this->registry->get($providerName);
        }

        // 2. Try by provider support
        $provider = $this->registry->findByProvider($providerName);
        if (null !== $provider) {
            return $provider;
        }

        // 3. Try container resolution
        return $this->tryResolveFromContainer($providerName);
    }

    /**
     * Sort providers by health status (healthy first).
     */
    private function sortProvidersByHealth(array $providers): array
    {
        usort($providers, function ($a, $b) {
            $healthA = $this->isProviderHealthy($a);
            $healthB = $this->isProviderHealthy($b);

            // Healthy providers first
            if ($healthA && !$healthB) {
                return -1;
            }
            if (!$healthA && $healthB) {
                return 1;
            }

            return 0; // Same health status, maintain order
        });

        return $providers;
    }

    /**
     * Initialize providers from configuration.
     */
    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->registerProvidersFromConfig();
        $this->initialized = true;
    }

    /**
     * Register providers based on mail.oauth2.providers configuration.
     */
    private function registerProvidersFromConfig(): void
    {
        try {
            $config = $this->container->get('config');

            // The ProviderRegistry has already done autoRegister in the container
            // Here we only need to log the final state, not re-register
            $availableProviders = $this->registry->getAvailable();

            if (empty($availableProviders)) {
                $this->logger->warning('No OAuth2 providers available after registry initialization');

                return;
            }

            // Log only the final state
            $this->logger->debugInternal('OAuth2 TokenProviderFactory initialized', [
                'available_providers' => $availableProviders,
                'provider_count' => \count($availableProviders),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize TokenProviderFactory', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate provider configuration.
     */
    private function validateProviderConfig(string $name, array $settings): bool
    {
        if (!isset($settings['class'])) {
            $this->logger->warning('Skipping provider registration - missing class', ['provider' => $name]);

            return false;
        }

        if (!class_exists($settings['class'])) {
            $this->logger->warning('Skipping provider registration - class does not exist', [
                'provider' => $name,
                'class' => $settings['class'],
            ]);

            return false;
        }

        if (!is_subclass_of($settings['class'], TokenProviderInterface::class)) {
            $this->logger->warning('Skipping provider registration - invalid interface', [
                'provider' => $name,
                'class' => $settings['class'],
            ]);

            return false;
        }

        return true;
    }

    /**
     * Try to resolve provider from container.
     */
    private function tryResolveFromContainer(string $providerName): ?TokenProviderInterface
    {
        if (class_exists($providerName) && $this->container->has($providerName)) {
            try {
                $provider = $this->container->get($providerName);
                if ($provider instanceof TokenProviderInterface) {
                    return $provider;
                }
            } catch (\Exception $e) {
                $this->logger->debugInternal('Failed to resolve provider from container', [
                    'provider' => $providerName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Update provider health status with timestamp.
     */
    private function updateProviderHealth(string $providerName, bool $isHealthy): void
    {
        $this->healthStatus[$providerName] = $isHealthy;
        $this->healthCheckedAt[$providerName] = time();

        $this->logger->debugInternal("Updated health status for provider: {$providerName}", [
            'healthy' => $isHealthy,
            'timestamp' => $this->healthCheckedAt[$providerName],
        ]);
    }
}
