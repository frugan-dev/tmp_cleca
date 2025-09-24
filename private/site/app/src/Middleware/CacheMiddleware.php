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
use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\HttpCache\Cache;

/**
 * Global HTTP Cache Middleware.
 *
 * Wraps Slim\HttpCache\Cache for global cache policy management.
 * Handles standard HTTP cache headers: Cache-Control, ETag validation, Last-Modified.
 * Applies consistent cache policies across the entire application.
 *
 * This differs from CacheHttpMiddleware which is route-specific and provides
 * advanced caching logic like body-based ETag generation for dynamic content.
 */
class CacheMiddleware extends Model implements MiddlewareInterface
{
    private ?Cache $middleware = null;
    private bool $initialized = false;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Lazy initialization on first use
        if (!$this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }

        // If the Cache middleware has been initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to the next handler
        return $handler->handle($request);
    }

    private function initialize(): void
    {
        // Check conditions to enable Cache middleware
        if (!$this->shouldEnable()) {
            return;
        }

        try {
            // Get cache configuration
            $type = $this->config->get('cache.http.type', 'private');
            $maxAge = $this->config->get('cache.http.maxAge', 86400);
            $mustRevalidate = $this->config->get('cache.http.mustRevalidate', false);

            // Create the Cache middleware
            $this->middleware = new Cache($type, $maxAge, $mustRevalidate);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Cache middleware: '.$e->getMessage());
        }
    }

    private function shouldEnable(): bool
    {
        // Don't enable in CLI mode - HTTP cache makes no sense for CLI
        if ($this->helper->Env()->isCli()) {
            return false;
        }

        // Check if HTTP cache middleware is enabled
        $cacheConfig = $this->config->get('cache.http.middleware', true);

        // If it's a boolean, use it directly
        if (\is_bool($cacheConfig)) {
            return $cacheConfig;
        }

        // If it's an array, check the 'enabled' key
        if (\is_array($cacheConfig)) {
            return $this->config->get('cache.http.middleware.enabled', true);
        }

        // Default: check the existing config key for backward compatibility
        return $this->config->get('cache.http.middleware.enabled', false);
    }
}
