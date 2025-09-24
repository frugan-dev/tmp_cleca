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

namespace App\Factory\Mailer\Provider\OAuth2\Mock;

use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\OAuth2\TokenProvider\AbstractTokenProvider;
use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Mock OAuth2 Token Provider for testing with mock-oauth2-server.
 *
 * @see https://github.com/navikt/mock-oauth2-server
 */
class MockOAuthTokenProvider extends AbstractTokenProvider
{
    private const string SERVER_URL = 'http://mock-oauth2:8080';
    private const string ISSUER = 'default';
    private const string SCOPE = 'https://outlook.office365.com/.default';
    private const string GRANT_TYPE = 'client_credentials';
    private const int TOKEN_TTL = 3600;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
        protected ?CacheInterface $cache = null,
        protected readonly string $serverUrl = self::SERVER_URL,
        protected readonly string $issuer = self::ISSUER,
        protected readonly string $scope = self::SCOPE
    ) {
        parent::__construct($container, $logger, $helper, $cache);
    }

    public function getProviderName(): string
    {
        return 'mock';
    }

    public function supports(string $provider): bool
    {
        return \in_array($provider, [
            'mock',
            'test',
            'development',
            'local',
        ], true);
    }

    /**
     * Factory method for easier DI configuration.
     */
    public static function fromConfig(
        ContainerInterface $container,
        LoggerInterface $logger,
        HelperInterface $helper,
        array $config,
        ?CacheInterface $cache = null
    ): self {
        return new self(
            $container,
            $logger,
            $helper,
            $cache,
            $config['server_url'] ?? self::SERVER_URL,
            $config['issuer'] ?? self::ISSUER,
            $config['scope'] ?? self::SCOPE
        );
    }

    /**
     * Get mock-specific configuration.
     */
    public function getInfo(): array
    {
        return [
            'server_url' => $this->serverUrl,
            'issuer' => $this->issuer,
            'scope' => $this->scope,
            'grant_type' => self::GRANT_TYPE,
            'token_ttl' => self::TOKEN_TTL,
            'fallback_enabled' => false,
            'server_type' => 'navikt/mock-oauth2-server',
        ];
    }

    protected function doFetchToken(): array
    {
        // NO FALLBACK - If server fails, this provider fails
        return $this->fetchFromMockServer();
    }

    #[\Override]
    protected function doHealthCheckRequest(): array
    {
        // For mock server, just do a simple connectivity test
        $urlParts = \Safe\parse_url($this->serverUrl);
        $host = $urlParts['host'] ?? 'localhost';
        $port = $urlParts['port'] ?? 8080;

        try {
            // Simple TCP connection test
            $connection = @\Safe\fsockopen($host, $port, $errno, $errstr, 3);

            if (!$connection) {
                throw new \Exception("Cannot connect to {$host}:{$port} - {$errstr} ({$errno})");
            }

            \Safe\fclose($connection);

            $this->logger->debugInternal('Mock server health check passed via TCP connectivity');

            // Return minimal success token data for health check
            return [
                'access_token' => 'health_check_token',
                'token_type' => 'Bearer',
                'expires_in' => self::TOKEN_TTL,
                'scope' => $this->scope,
            ];
        } catch (\Exception $e) {
            $this->logger->debugInternal('Mock server health check failed, trying token fetch', [
                'health_error' => $e->getMessage(),
            ]);

            // Fallback to actual token fetch with short timeout
            return $this->fetchFromMockServer(3);
        }
    }

    /**
     * Fetch token from navikt/mock-oauth2-server.
     */
    private function fetchFromMockServer(int $timeout = 10): array
    {
        $tokenUrl = $this->serverUrl.'/'.$this->issuer.'/token';

        $postData = [
            'client_id' => 'mock-client',
            'client_secret' => 'mock-secret',
            'scope' => $this->scope,
            'grant_type' => self::GRANT_TYPE,
        ];

        try {
            $data = $this->makeOAuth2Request($tokenUrl, $postData, $timeout);

            if (!isset($data['expires_in'])) {
                $data['expires_in'] = self::TOKEN_TTL;
            }

            $this->logger->debugInternal('Mock OAuth2 server token fetched successfully', [
                'server_url' => $this->serverUrl,
                'issuer' => $this->issuer,
            ]);

            return $data;
        } catch (\Exception $e) {
            throw new \Exception("Mock OAuth2 server unavailable: {$e->getMessage()}");
        }
    }
}
