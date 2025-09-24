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
use App\Factory\Mailer\OAuth2\Authenticator\XOAuth2Authenticator;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderFactory;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\Dsn;
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
     * Configure OAuth2 authentication for the transport.
     */
    private function configureOAuth2Transport(EsmtpTransport $transport, Dsn $dsn): void
    {
        try {
            $providerName = $this->extractProviderName($dsn);

            // Create authenticator instance for this specific provider
            $authenticator = $this->createAuthenticatorForProvider($providerName);

            // Add OAuth2 authenticator to transport
            $transport->addAuthenticator($authenticator);

            // Set provider hint in username for authenticator
            if ($providerName) {
                $transport->setUsername("oauth2:{$providerName}");
            }

            // Set a placeholder password that will be replaced by the authenticator
            $transport->setPassword('oauth2_token_placeholder');

            $this->logger->debugInternal('OAuth2 transport configured', [
                'provider' => $providerName,
                'host' => $dsn->getHost(),
                'port' => $dsn->getPort(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to configure OAuth2 transport', [
                'error' => $e->getMessage(),
                'dsn_scheme' => $dsn->getScheme(),
                'dsn_host' => $dsn->getHost(),
                'dsn_port' => $dsn->getPort(),
            ]);

            throw $e;
        }
    }

    /**
     * Create authenticator for specific provider or with fallback.
     */
    private function createAuthenticatorForProvider(?string $providerName): XOAuth2Authenticator
    {
        try {
            $provider = null;

            if ($providerName) {
                $provider = $this->tokenProviderFactory->create($providerName);
            } else {
                $provider = $this->tokenProviderFactory->createWithFallback();
            }

            return new XOAuth2Authenticator($this->container, $this->tokenProviderFactory, $provider);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create OAuth2 authenticator', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract provider name from DSN options.
     */
    private function extractProviderName(Dsn $dsn): ?string
    {
        // Check for oauth2_provider parameter in DSN
        return $dsn->getOption('oauth2_provider');
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
