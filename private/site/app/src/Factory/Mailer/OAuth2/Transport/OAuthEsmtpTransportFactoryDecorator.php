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

namespace App\Factory\Mailer\OAuth2\Transport;

use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Enhanced OAuth2 Transport Factory with multi-provider support.
 *
 * https://gist.github.com/dbu/3094d7569aebfc94788b164bd7e59acc
 */
class OAuthEsmtpTransportFactoryDecorator implements TransportFactoryInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected TransportFactoryInterface $inner,
        protected TokenProviderFactory $tokenProviderFactory,
        protected LoggerInterface $logger
    ) {}

    public function create(Dsn $dsn): TransportInterface
    {
        // For OAuth2 scheme, convert to SMTP for inner factory
        $actualDsn = $this->convertOAuth2DsnToSmtp($dsn);
        $transport = $this->inner->create($actualDsn);

        // Handle OAuth2 transport type or SMTP with OAuth2 indicators
        if ($this->shouldUseOAuth2($dsn, $transport)) {
            if ($transport instanceof EsmtpTransport) {
                $this->configureOAuth2Transport($transport, $dsn);
            }
        }

        return $transport;
    }

    public function supports(Dsn $dsn): bool
    {
        // Support 'oauth2' scheme or delegate to inner factory
        if ('oauth2' === $dsn->getScheme()) {
            // Convert oauth2:// to smtp:// for inner factory check
            $smtpDsn = $this->convertOAuth2DsnToSmtp($dsn);

            return $this->inner->supports($smtpDsn);
        }

        return $this->inner->supports($dsn);
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    protected function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null): mixed
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "mail.{$env}",
            'mail',
        ];

        $config = $this->container->get('config');

        return $config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    /**
     * Configure OAuth2 authentication on transport using Symfony's built-in authenticator.
     */
    private function configureOAuth2Transport(EsmtpTransport $transport, Dsn $dsn): void
    {
        try {
            $providerName = $this->extractProviderName($dsn);

            if (!$providerName) {
                throw new \Exception('OAuth2 provider name not found in DSN');
            }

            $this->logger->debugInternal('Configuring OAuth2 transport', [
                'provider' => $providerName,
                'host' => $dsn->getHost(),
                'port' => $dsn->getPort(),
            ]);

            // Get OAuth2 token from provider
            $provider = $this->tokenProviderFactory->create($providerName);
            $accessToken = $provider->getAccessToken();

            // Configure transport for built-in XOAuth2Authenticator
            $transport->setUsername($dsn->getUser()); // Real email
            $transport->setPassword($accessToken); // OAuth2 token

            // Force OAuth2-only authentication by removing all non-XOAUTH2 authenticators.
            //
            // PRODUCTION USE CASE:
            // When disabled, Symfony's EsmtpTransport tries authenticators in this order:
            // 1. CRAM-MD5 -> 2. LOGIN -> 3. PLAIN -> 4. XOAUTH2
            // Each failed attempt against production SMTP servers (e.g., Microsoft Office365) that
            // don't support those methods causes a ~3 seconds timeout before trying the next one.
            // This results in ~10 seconds of latency before XOAUTH2 is attempted. Enabling this
            // keeps only XOAUTH2, making authentication immediate.
            //
            // DEVELOPMENT USE CASE:
            // Required to enforce OAuth2 with mock servers. This ensures OAuth2 implementation
            // works correctly without silently falling back to other methods (e.g., Mailpit
            // supports PLAIN/LOGIN but not OAuth2, causing authentication to succeed with wrong
            // method).
            $forceOnly = $this->getConfigWithFallback('oauth2.force_only', false);
            if ($forceOnly) {
                // method #1 - Remove all authenticators except XOAUTH2
                // $this->removeNonOAuth2Authenticators($transport);

                // method #2 - Set only XOAUTH2 directly
                $transport->setAuthenticators([new XOAuth2Authenticator()]);
            }

            $this->logger->debugInternal('OAuth2 transport configured successfully', [
                'provider' => $providerName,
                'host' => $dsn->getHost(),
                'port' => $dsn->getPort(),
                'username' => $dsn->getUser(),
                'token_length' => \strlen($accessToken),
                'force_only' => $forceOnly,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to configure OAuth2 transport', [
                'exception' => $e,
                'error' => $e->getMessage(),
                'text' => $e->getTraceAsString(),
                'provider' => $providerName ?? 'unknown',
                'dsn_scheme' => $dsn->getScheme(),
                'dsn_host' => $dsn->getHost(),
                'dsn_port' => $dsn->getPort(),
            ]);

            throw $e;
        }
    }

    /**
     * Remove all authenticators except XOAUTH2 to force OAuth2-only authentication.
     */
    private function removeNonOAuth2Authenticators(EsmtpTransport $transport): void
    {
        try {
            $reflection = new \ReflectionClass($transport);
            $property = $reflection->getProperty('authenticators');

            $authenticators = $property->getValue($transport);
            $xoauth2Only = array_filter($authenticators, fn ($auth) => $auth instanceof XOAuth2Authenticator);

            $property->setValue($transport, array_values($xoauth2Only));

            $this->logger->debugInternal('OAuth2-only authentication enforced', [
                'total_authenticators' => \count($authenticators),
                'oauth2_authenticators' => \count($xoauth2Only),
            ]);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to enforce OAuth2-only authentication', [
                'exception' => $e,
            ]);
            // Non-critical error, continue without forcing OAuth2-only
        }
    }

    /**
     * Extract provider name from DSN options.
     */
    private function extractProviderName(Dsn $dsn): ?string
    {
        // First check if oauth2_provider is in the DSN options
        $providerName = $dsn->getOption('oauth2_provider');

        if ($providerName) {
            return $providerName;
        }

        // Fallback: check if it's in the DSN string directly
        // This shouldn't normally happen, but provides a safety net
        return null;
    }

    /**
     * Convert oauth2:// DSN to smtp:// for inner factory.
     */
    private function convertOAuth2DsnToSmtp(Dsn $dsn): Dsn
    {
        if ('oauth2' !== $dsn->getScheme()) {
            return $dsn;
        }

        return new Dsn(
            'smtp',
            $dsn->getHost(),
            $dsn->getUser(),
            '', // Empty password for OAuth2
            $dsn->getPort(),
            $dsn->getOptions()
        );
    }

    /**
     * Determine if OAuth2 should be used for this transport.
     */
    private function shouldUseOAuth2(Dsn $dsn, TransportInterface $transport): bool
    {
        // Use OAuth2 if:
        // 1. Scheme is 'oauth2'
        // 2. Or it's an SMTP transport with oauth2_provider option
        // 3. Or it's an SMTP transport with empty password (OAuth2 indicator)
        return 'oauth2' === $dsn->getScheme()
               || $dsn->getOption('oauth2_provider')
               || ($transport instanceof EsmtpTransport && empty($dsn->getPassword()));
    }
}
