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
use Middlewares\Https;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// FIXED - https://github.com/middlewares/https/issues/5
class HttpsMiddleware extends Model implements MiddlewareInterface
{
    private ?Https $middleware = null;
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

        // If the Https middleware has been initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to the next handler
        return $handler->handle($request);
    }

    private function initialize(): void
    {
        // Check conditions to enable Https
        if (!$this->shouldEnable()) {
            return;
        }

        $env = $this->container->get('env');

        try {
            // Create the Https middleware
            $this->middleware = new Https();

            // Configure HSTS settings if available
            if ($this->config->has('url.https.maxAge')) {
                $this->middleware = $this->middleware->maxAge($this->config->get('url.https.maxAge'));
            }

            if ($this->config->has('url.https.includeSubdomains')) {
                $this->middleware = $this->middleware->includeSubdomains($this->config->get('url.https.includeSubdomains'));
            }

            if ($this->config->has('url.https.preload')) {
                $this->middleware = $this->middleware->preload($this->config->get('url.https.preload'));
            }

            if ($this->config->has('url.https.checkHttpsForward')) {
                $this->middleware = $this->middleware->checkHttpsForward($this->config->get('url.https.checkHttpsForward'));
            }

            // Add redirect functionality only if not in CLI mode and redirect is enabled
            if (!$this->helper->Env()->isCli() && $this->config->get('url.https.redirect', true)) {
                $this->middleware = $this->middleware->redirect($this->config->get('url.https'));
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Https middleware: '.$e->getMessage());
        }
    }

    private function shouldEnable(): bool
    {
        // Don't enable in CLI mode
        if ($this->helper->Env()->isCli()) {
            return false;
        }

        // Check if HTTPS processing is enabled
        $httpsConfig = $this->config->get('url.https', false);

        // If it's a boolean, use it directly
        if (\is_bool($httpsConfig)) {
            return $httpsConfig;
        }

        // If it's an array, check the 'enabled' key
        if (\is_array($httpsConfig)) {
            return $this->config->get('url.https.enabled', true);
        }

        // Default: disabled
        return false;
    }
}
