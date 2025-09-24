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

namespace App\Factory\Mailer\Transport;

use App\Factory\Logger\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Registry for managing custom transport factories.
 */
class TransportFactoryRegistry
{
    private array $factories = [];

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger
    ) {}

    /**
     * Register all custom transport factories.
     */
    public function registerFactories(): void
    {
        if (!empty($this->factories)) {
            return; // Already registered
        }

        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $client = $this->container->get(HttpClientInterface::class);

        // Register custom transport factories
        $this->factories = [
            new MailTransportFactory($dispatcher, $client, $this->logger->channel()),
            // Add other custom factories here if needed
        ];

        $this->logger->debugInternal('Registered custom transport factories', [
            'count' => \count($this->factories),
        ]);
    }

    /**
     * Get all registered factories.
     */
    public function getFactories(): array
    {
        if (empty($this->factories)) {
            $this->registerFactories();
        }

        return $this->factories;
    }
}
