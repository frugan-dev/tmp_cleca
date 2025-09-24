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
use Middlewares\TrailingSlash;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TrailingSlashMiddleware extends Model implements MiddlewareInterface
{
    private ?TrailingSlash $middleware = null;
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

        // If the TrailingSlash middleware has been initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to the next handler
        return $handler->handle($request);
    }

    private function initialize(): void
    {
        // Check conditions to enable TrailingSlash
        if (!$this->shouldEnable()) {
            return;
        }

        try {
            // Create the TrailingSlash middleware
            $this->middleware = new TrailingSlash(true)->redirect();
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize TrailingSlash middleware: '.$e->getMessage());
        }
    }

    private function shouldEnable(): bool
    {
        // Don't enable in CLI mode
        if ($this->helper->Env()->isCli()) {
            return false;
        }

        // Check if trailing slash processing is enabled
        $trailingSlashConfig = $this->config->get('url.trailingSlash', false);

        // If it's a boolean, use it directly (but only enable if true)
        if (\is_bool($trailingSlashConfig)) {
            return $trailingSlashConfig;
        }

        // If it's an array, check the 'enabled' key
        if (\is_array($trailingSlashConfig)) {
            return $this->config->get('url.trailingSlash.enabled', true);
        }

        // Default: disabled
        return false;
    }
}
