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
 * Fake OAuth Token Provider - Pure JWT generation without external server.
 * Can be configured to simulate failures for testing.
 */
class FakeOAuthTokenProvider extends AbstractTokenProvider
{
    private const string SCOPE = 'https://outlook.office365.com/.default';
    private const string GRANT_TYPE = 'client_credentials';
    private const int TOKEN_TTL = 3600;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
        protected ?CacheInterface $cache = null,
        protected readonly string $scope = self::SCOPE,
        protected readonly bool $simulateFetchTokenFailure = false,
        protected readonly bool $simulateHealthCheckFailure = false,
        protected readonly ?string $failureMessage = null,
        protected readonly int $tokenTtl = self::TOKEN_TTL
    ) {
        parent::__construct($container, $logger, $helper, $cache);
    }

    public function getProviderName(): string
    {
        return 'fake';
    }

    public function supports(string $provider): bool
    {
        return \in_array($provider, [
            'fake',
            'jwt',
            'static',
            'dummy',
        ], true);
    }

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
            $config['scope'] ?? self::SCOPE,
            $config['simulate_fetch_token_failure'] ?? false,
            $config['simulate_health_check_failure'] ?? false,
            $config['failure_message'] ?? null,
            $config['token_ttl'] ?? self::TOKEN_TTL
        );
    }

    public function getInfo(): array
    {
        return [
            'scope' => $this->scope,
            'grant_type' => self::GRANT_TYPE,
            'token_ttl' => $this->tokenTtl,
            'simulate_fetch_token_failure' => $this->simulateFetchTokenFailure,
            'simulate_health_check_failure' => $this->simulateHealthCheckFailure,
            'failure_message' => $this->failureMessage,
            'server_type' => 'fake-jwt-generator',
            'fallback_enabled' => false,
        ];
    }

    protected function doFetchToken(): array
    {
        // Check if we should simulate a failure
        if ($this->simulateFetchTokenFailure) {
            $message = $this->failureMessage ?? 'Simulated OAuth2 fetch token failure';

            $this->logger->debugInternal('Fake OAuth2 provider simulating failure', [
                'message' => $message,
            ]);

            throw new \Exception($message);
        }

        return $this->generateFakeToken();
    }

    #[\Override]
    protected function doHealthCheckRequest(): array
    {
        // Same logic as doFetchToken for consistency
        if ($this->simulateHealthCheckFailure) {
            $message = $this->failureMessage ?? 'Simulated health check failure';

            throw new \Exception($message);
        }

        return $this->generateFakeToken();
    }

    /**
     * Generate a fake OAuth2 token response.
     */
    private function generateFakeToken(): array
    {
        $token = $this->generateFakeJWT();

        $this->logger->debugInternal('Generated fake OAuth2 token', [
            'provider' => $this->getProviderName(),
            'scope' => $this->scope,
            'ttl' => $this->tokenTtl,
        ]);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->tokenTtl,
            'scope' => $this->scope,
        ];
    }

    /**
     * Generate a fake JWT-like token.
     */
    private function generateFakeJWT(): string
    {
        $header = \Safe\json_encode([
            'typ' => 'JWT',
            'alg' => 'none',
        ]);

        $payload = \Safe\json_encode([
            'iss' => 'fake-oauth2-provider',
            'sub' => 'fake-client',
            'aud' => 'https://outlook.office365.com',
            'exp' => time() + $this->tokenTtl,
            'iat' => time(),
            'nbf' => time(),
            'jti' => bin2hex(random_bytes(16)),
            'scope' => $this->scope,
            'provider' => 'fake',
            'client_id' => 'fake-client-id',
            'generated_at' => date('c'),
        ]);

        $headerEncoded = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

        return $headerEncoded.'.'.$payloadEncoded.'.fake-signature-'.bin2hex(random_bytes(16));
    }
}
