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

namespace App\Factory\Mailer\Provider\OAuth2\Microsoft;

use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\OAuth2\TokenProvider\AbstractTokenProvider;
use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * https://gist.github.com/dbu/3094d7569aebfc94788b164bd7e59acc.
 *
 * Simplified OAuth Token Provider using Client Credentials Grant
 * No initial setup required - works directly with app credentials
 */
class Office365OAuthTokenProvider extends AbstractTokenProvider
{
    private const string OAUTH_URL = 'https://login.microsoftonline.com/%s/oauth2/v2.0/token';
    private const string SCOPE = 'https://outlook.office365.com/.default';
    private const string GRANT_TYPE = 'client_credentials';

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
        protected ?CacheInterface $cache,
        #[\SensitiveParameter]
        protected readonly string $tenant,
        #[\SensitiveParameter]
        protected readonly string $clientId,
        #[\SensitiveParameter]
        protected readonly string $clientSecret,
        protected readonly string $scope = self::SCOPE
    ) {
        parent::__construct($container, $logger, $helper, $cache);
    }

    public function getProviderName(): string
    {
        return 'microsoft-office365';
    }

    public function supports(string $provider): bool
    {
        return \in_array($provider, [
            'microsoft-office365',
            'office365',
            'microsoft',
            'outlook',
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
            $config['tenant'] ?? throw new \InvalidArgumentException('Missing tenant'),
            $config['client_id'] ?? throw new \InvalidArgumentException('Missing client_id'),
            $config['client_secret'] ?? throw new \InvalidArgumentException('Missing client_secret'),
            $config['scope'] ?? self::SCOPE
        );
    }

    /**
     * Get Microsoft-specific information.
     */
    public function getInfo(): array
    {
        return [
            'tenant' => $this->tenant,
            'scope' => $this->scope,
            'grant_type' => self::GRANT_TYPE,
        ];
    }

    protected function doFetchToken(): array
    {
        $tokenUrl = \sprintf(self::OAUTH_URL, $this->tenant);

        $postData = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => $this->scope,
            'grant_type' => self::GRANT_TYPE,
        ];

        return $this->makeOAuth2Request($tokenUrl, $postData);
    }
}
