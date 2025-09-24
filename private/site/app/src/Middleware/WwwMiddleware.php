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
use Middlewares\Www;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WwwMiddleware extends Model implements MiddlewareInterface
{
    private ?Www $middleware = null;
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

        // If the Www middleware has been initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to the next handler
        return $handler->handle($request);
    }

    private function initialize(): void
    {
        // Check conditions to enable Www
        if (!$this->shouldEnable()) {
            return;
        }

        try {
            // Get the www configuration value
            $wwwConfig = $this->config->get('url.www', false);
            $wwwValue = false;

            // Determine the boolean value to pass to Www middleware
            if (\is_bool($wwwConfig)) {
                $wwwValue = $wwwConfig;
            } elseif (\is_array($wwwConfig)) {
                $wwwValue = $this->config->get('url.www.add', false);
            }

            // Create the Www middleware
            $this->middleware = new Www($wwwValue);
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Www middleware: '.$e->getMessage());
        }
    }

    private function shouldEnable(): bool
    {
        // Don't enable in CLI mode
        if ($this->helper->Env()->isCli()) {
            return false;
        }

        // Check if www processing is enabled
        $wwwConfig = $this->config->get('url.www', false);

        // If it's a boolean, enable only if true (we need to process www in both directions)
        if (\is_bool($wwwConfig)) {
            // Enable middleware for both true (add www) and false (remove www)
            // but only if the config key exists
            return $this->config->has('url.www');
        }

        // If it's an array, check the 'enabled' key
        if (\is_array($wwwConfig)) {
            return $this->config->get('url.www.enabled', true);
        }

        // Default: disabled
        return false;
    }
}
