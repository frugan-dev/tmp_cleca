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

namespace App\Factory\Mailer\OAuth2\Authenticator;

use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderFactory;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator as BaseXOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Enhanced OAuth2 Authenticator that supports dynamic provider selection.
 *
 * https://gist.github.com/dbu/3094d7569aebfc94788b164bd7e59acc
 */
class XOAuth2Authenticator implements AuthenticatorInterface
{
    private readonly BaseXOAuth2Authenticator $authenticator;

    public function __construct(
        protected ContainerInterface $container,
        protected TokenProviderFactory $tokenProviderFactory,
        protected ?TokenProviderInterface $currentProvider = null
    ) {
        $this->authenticator = new BaseXOAuth2Authenticator();
    }

    public function getAuthKeyword(): string
    {
        return $this->authenticator->getAuthKeyword();
    }

    public function authenticate(EsmtpTransport $client): void
    {
        // Get provider from transport context or use current provider
        $provider = $this->resolveProvider($client);

        if (!$provider) {
            throw new \RuntimeException('No OAuth2 provider available for authentication');
        }

        // Get fresh token from the provider
        $freshToken = $provider->getAccessToken();

        // Temporarily replace client password with fresh token
        $originalPassword = $client->getPassword();
        $client->setPassword($freshToken);

        try {
            // Use basic authenticator with fresh token
            $this->authenticator->authenticate($client);
        } finally {
            // Reset the original password (even if it is empty)
            $client->setPassword($originalPassword);
        }
    }

    /**
     * Set the provider for this authenticator instance.
     */
    public function setProvider(TokenProviderInterface $provider): self
    {
        $this->currentProvider = $provider;

        return $this;
    }

    /**
     * Resolve provider from transport context or fallback.
     */
    private function resolveProvider(EsmtpTransport $client): ?TokenProviderInterface
    {
        // Try to get provider from transport metadata (if set by factory)
        $providerName = $this->getProviderFromTransport($client);

        if ($providerName) {
            try {
                return $this->tokenProviderFactory->create($providerName);
            } catch (\Exception $e) {
                // Log error but continue with fallback
                $this->container->get('logger')?->warning(
                    'Failed to create provider from transport context',
                    ['provider' => $providerName, 'error' => $e->getMessage()]
                );
            }
        }

        // Use current provider if available
        if ($this->currentProvider) {
            return $this->currentProvider;
        }

        // Last resort: try to create with fallback
        try {
            return $this->tokenProviderFactory->createWithFallback();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Extract provider name from transport (could be stored in username or custom property).
     */
    private function getProviderFromTransport(EsmtpTransport $client): ?string
    {
        $username = $client->getUsername();

        // Check if username contains provider hint (e.g., "oauth2:microsoft-office365")
        if (str_starts_with($username, 'oauth2:')) {
            return substr($username, 7);
        }

        return null;
    }
}
