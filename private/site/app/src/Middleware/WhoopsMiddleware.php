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

namespace App\Middleware;

use App\Factory\Logger\LoggerInterface;
use App\Model\Model;
use Middlewares\Whoops;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WhoopsMiddleware extends Model implements MiddlewareInterface
{
    private ?Whoops $middleware = null;
    private bool $initialized = false;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Lazy initialization on first use
        if (!$this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }

        // If the Whoops middleware has been initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to the next handler
        return $handler->handle($request);
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    protected function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "debug.{$env}",
            'debug',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    private function initialize(): void
    {
        // Check conditions to enable Whoops
        if (!$this->shouldEnable()) {
            return;
        }

        $env = $this->container->get('env');

        try {
            // Create the Whoops middleware
            $this->middleware = new Whoops()->catchErrors($this->getConfigWithFallback('whoops.catchErrors'));
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Whoops middleware: '.$e->getMessage());
        }
    }

    private function shouldEnable(): bool
    {
        return $this->getConfigWithFallback('enabled')
            && $this->getConfigWithFallback('whoops.enabled');
    }
}
