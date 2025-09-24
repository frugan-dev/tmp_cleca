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

use App\Factory\Debugbar\DebugbarInterface;
use App\Factory\Logger\LoggerInterface;
use App\Model\Model;
use Middlewares\Debugbar;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// FIXED - it needs Response mime Content-type
// FIXED - If you use a catch-all/fallback route, make sure you load the Debugbar ServiceProvider before your own App ServiceProviders.
// https://github.com/barryvdh/laravel-debugbar/issues/187#issuecomment-285863552
class DebugbarMiddleware extends Model implements MiddlewareInterface
{
    private ?Debugbar $middleware = null;
    private bool $initialized = false;

    public function __construct(
        protected ContainerInterface $container,
        protected DebugbarInterface $debugbar,
        protected LoggerInterface $logger,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Lazy initialization at first use
        if (!$this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }

        // If debugbar middleware is initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to next handler
        return $handler->handle($request);
    }

    private function initialize(): void
    {
        // Check if debugbar is enabled
        if (!$this->debugbar->isEnabled()) {
            return;
        }

        try {
            // Get the debugbar instance
            $debugbarInstance = $this->debugbar->getInstance();

            if (null === $debugbarInstance) {
                return;
            }

            // Create the Debugbar middleware
            $this->middleware = new Debugbar($debugbarInstance);

            // Configure the middleware
            $this->configure();
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Debugbar middleware: '.$e->getMessage());
        }
    }

    private function configure(): void
    {
        if (null === $this->middleware) {
            return;
        }

        // Configure capture AJAX
        if ($this->config->get('debug.debugbar.captureAjax')) {
            $this->middleware->captureAjax();
        }

        // Configure inline mode
        if ($this->config->get('debug.debugbar.inline')) {
            $this->middleware->inline();
        }
    }
}
