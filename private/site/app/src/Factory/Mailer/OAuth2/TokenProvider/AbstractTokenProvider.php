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
use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractTokenProvider extends Model implements TokenProviderInterface
{
    protected const CACHE_TTL_BUFFER = 300; // 5 minutes buffer before expiry
    protected const MIN_CACHE_TTL = 60;     // Minimum 1 minute cache
    protected const MAX_RETRY_ATTEMPTS = 3;
    protected const RETRY_DELAY_MS = 1000;  // 1 second

    protected const HEALTH_CACHE_TTL = 300; // 5 minutes health cache
    protected const HEALTH_CACHE_FAILED_TTL = 60; // 1 minute for failed providers

    private ?bool $healthStatus = null;
    private ?int $healthCheckedAt = null;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
        protected ?CacheInterface $cache = null
    ) {}

    /**
     * Get a valid access token, using cache when available.
     */
    public function getAccessToken(): string
    {
        if ($this->cache) {
            $cacheKey = $this->getCacheKey();

            try {
                return $this->cache->get($cacheKey, $this->fetchToken(...));
            } catch (\Exception $e) {
                $this->logger->warning('Cache failed, fetching token directly', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                    'provider' => $this->getProviderName(),
                ]);

                // Fallback to direct fetch if cache fails
                return $this->fetchToken();
            }
        }

        return $this->fetchToken();
    }

    /**
     * Fetch token method for cache compatibility with retry logic.
     */
    public function fetchToken(?ItemInterface $cacheItem = null): string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= static::MAX_RETRY_ATTEMPTS; ++$attempt) {
            try {
                $this->logger->debugInternal('Fetching OAuth2 token for provider: {provider}', [
                    'provider' => $this->getProviderName(),
                    'attempt' => $attempt,
                    'max_attempts' => static::MAX_RETRY_ATTEMPTS,
                ]);

                $tokenData = $this->doFetchToken();

                // Set cache expiry if cache item is available
                if ($cacheItem && isset($tokenData['expires_in'])) {
                    $expiresIn = (int) $tokenData['expires_in'] - static::CACHE_TTL_BUFFER;
                    $cacheItem->expiresAfter(max(static::MIN_CACHE_TTL, $expiresIn));
                }

                $this->logger->debugInternal('OAuth2 token fetched successfully for provider: {provider}', [
                    'provider' => $this->getProviderName(),
                    'expires_in' => $tokenData['expires_in'] ?? 'unknown',
                    'token_type' => $tokenData['token_type'] ?? 'Bearer',
                    'attempt' => $attempt,
                ]);

                return $tokenData['access_token'];
            } catch (\Exception $e) {
                $lastException = $e;

                $this->logger->warning('OAuth2 token fetch attempt {attempt} failed for provider: {provider}', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                    'provider' => $this->getProviderName(),
                    'attempt' => $attempt,
                    'max_attempts' => static::MAX_RETRY_ATTEMPTS,
                ]);

                // Wait before retry (except on last attempt)
                if ($attempt < static::MAX_RETRY_ATTEMPTS) {
                    usleep(static::RETRY_DELAY_MS * 1000 * $attempt); // Exponential backoff
                }
            }
        }

        // All attempts failed
        $this->logger->error('All OAuth2 token fetch attempts failed for provider: {provider}', [
            'provider' => $this->getProviderName(),
            'total_attempts' => static::MAX_RETRY_ATTEMPTS,
            'last_error' => $lastException?->getMessage(),
        ]);

        throw new \Exception(
            "OAuth2 token fetch failed for {$this->getProviderName()} after ".static::MAX_RETRY_ATTEMPTS.' attempts: '.$lastException?->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * Abstract method: return provider name.
     */
    abstract public function getProviderName(): string;

    /**
     * Abstract method: check if this provider supports the given provider name.
     */
    abstract public function supports(string $provider): bool;

    /**
     * Check if this provider is currently healthy/available with caching.
     */
    public function isHealthy(): bool
    {
        $now = time();

        // Check in-memory cache first
        if (null !== $this->healthStatus && null !== $this->healthCheckedAt) {
            $cacheAge = $now - $this->healthCheckedAt;
            $maxAge = $this->healthStatus ? self::HEALTH_CACHE_TTL : self::HEALTH_CACHE_FAILED_TTL;

            if ($cacheAge < $maxAge) {
                $this->logger->debugInternal('Using cached health status for {provider}', [
                    'provider' => $this->getProviderName(),
                    'healthy' => $this->healthStatus,
                    'cache_age' => $cacheAge,
                    'max_age' => $maxAge,
                ]);

                return $this->healthStatus;
            }
        }

        // Check persistent cache if available
        if ($this->cache) {
            $cacheKey = $this->getHealthCacheKey();

            try {
                $cachedHealth = $this->cache->get($cacheKey, fn () => $this->performHealthCheck());

                // Update in-memory cache
                $this->healthStatus = $cachedHealth['healthy'];
                $this->healthCheckedAt = $cachedHealth['checked_at'];

                return $this->healthStatus;
            } catch (\Exception $e) {
                $this->logger->debugInternal('Health cache failed, performing direct check', [
                    'exception' => $e,
                    'provider' => $this->getProviderName(),
                ]);
            }
        }

        // Fallback: direct health check
        $result = $this->performHealthCheck();

        // Update in-memory cache
        $this->healthStatus = $result['healthy'];
        $this->healthCheckedAt = $result['checked_at'];

        return $this->healthStatus;
    }

    /**
     * Force a fresh health check, bypassing cache.
     */
    public function checkHealthFresh(): bool
    {
        $this->clearHealthCache();

        $result = $this->performHealthCheck();

        // Update caches
        $this->healthStatus = $result['healthy'];
        $this->healthCheckedAt = $result['checked_at'];

        // Update persistent cache if available
        if ($this->cache) {
            try {
                $cacheKey = $this->getHealthCacheKey();
                $ttl = $result['healthy'] ? self::HEALTH_CACHE_TTL : self::HEALTH_CACHE_FAILED_TTL;

                $this->cache->delete($cacheKey); // Clear old cache
                $this->cache->set($cacheKey, $result, $ttl);
            } catch (\Exception $e) {
                $this->logger->warning('Failed to update health cache', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                    'provider' => $this->getProviderName(),
                ]);
            }
        }

        return $this->healthStatus;
    }

    /**
     * Clear health cache for this provider.
     */
    public function clearHealthCache(): void
    {
        $this->healthStatus = null;
        $this->healthCheckedAt = null;

        if ($this->cache) {
            try {
                $this->cache->delete($this->getHealthCacheKey());
            } catch (\Exception $e) {
                $this->logger->debugInternal('Failed to clear health cache', [
                    'exception' => $e,
                    'provider' => $this->getProviderName(),
                ]);
            }
        }
    }

    /**
     * Get health information including cache status.
     */
    public function getHealthInfo(): array
    {
        $now = time();

        return [
            'provider' => $this->getProviderName(),
            'healthy' => $this->healthStatus,
            'last_checked' => $this->healthCheckedAt,
            'cache_age' => $this->healthCheckedAt ? $now - $this->healthCheckedAt : null,
            'cache_fresh' => $this->isHealthCacheFresh(),
        ];
    }

    /**
     * Get cache key for this provider.
     */
    protected function getCacheKey(): string
    {
        return 'oauth2_token_'.str_replace('-', '_', $this->getProviderName());
    }

    /**
     * Make HTTP request to OAuth2 endpoint with enhanced error handling.
     */
    protected function makeOAuth2Request(string $url, array $postData, int $timeout = 30): array
    {
        try {
            $response = $this->helper->Remote()->request([
                'method' => 'POST',
                'url' => $url,
                'options' => [
                    'timeout' => $timeout,
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accept' => 'application/json',
                        'User-Agent' => $this->getUserAgent(),
                    ],
                    'form_params' => $postData,
                ],
                'return_type' => 'body',
            ]);

            if (false === $response) {
                throw new \Exception('Failed to get response from OAuth endpoint');
            }

            $data = \Safe\json_decode($response, true);

            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new \Exception('Invalid JSON response from OAuth endpoint: '.json_last_error_msg());
            }

            if (isset($data['error'])) {
                $errorMsg = "OAuth2 Error: {$data['error']}";
                if (isset($data['error_description'])) {
                    $errorMsg .= " - {$data['error_description']}";
                }
                if (isset($data['error_uri'])) {
                    $errorMsg .= " (See: {$data['error_uri']})";
                }

                throw new \Exception($errorMsg);
            }

            if (!isset($data['access_token'])) {
                throw new \Exception('Access token not found in OAuth response');
            }

            return $data;
        } catch (\Exception $e) {
            // Re-throw with provider context
            throw new \Exception(
                "OAuth2 request failed for {$this->getProviderName()}: ".$e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Get User-Agent string for HTTP requests.
     */
    protected function getUserAgent(): string
    {
        return \sprintf(
            'SlimMailer/1.0 (%s OAuth2 Provider)',
            $this->getProviderName()
        );
    }

    /**
     * Abstract method: implement token fetching logic for specific provider.
     */
    abstract protected function doFetchToken(): array;

    /**
     * Perform a lightweight health check request.
     * Override this method in subclasses for provider-specific optimization.
     */
    protected function doHealthCheckRequest(): array
    {
        // Default: try to fetch a token (same as doFetchToken)
        return $this->doFetchToken();
    }

    /**
     * Perform actual health check with timeout and error handling.
     */
    private function performHealthCheck(): array
    {
        $startTime = microtime(true);
        $now = time();

        try {
            $this->logger->debugInternal('Performing health check for provider: {provider}', [
                'provider' => $this->getProviderName(),
            ]);

            // Quick test with minimal scope and short timeout
            $tokenData = $this->doHealthCheckRequest();

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->debugInternal('Health check passed for provider: {provider}', [
                'provider' => $this->getProviderName(),
                'duration_ms' => $duration,
            ]);

            return [
                'healthy' => true,
                'checked_at' => $now,
                'duration_ms' => $duration,
            ];
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logger->debugInternal('Health check failed for provider: {provider}', [
                'exception' => $e,
                'provider' => $this->getProviderName(),
                'duration_ms' => $duration,
            ]);

            return [
                'exception' => $e,
                'healthy' => false,
                'checked_at' => $now,
                'duration_ms' => $duration,
            ];
        }
    }

    /**
     * Check if health cache is still fresh.
     */
    private function isHealthCacheFresh(): bool
    {
        if (null === $this->healthCheckedAt) {
            return false;
        }

        $cacheAge = time() - $this->healthCheckedAt;
        $maxAge = $this->healthStatus ? self::HEALTH_CACHE_TTL : self::HEALTH_CACHE_FAILED_TTL;

        return $cacheAge < $maxAge;
    }

    /**
     * Get cache key for health status.
     */
    private function getHealthCacheKey(): string
    {
        return 'oauth2_health_'.str_replace('-', '_', $this->getProviderName());
    }
}
