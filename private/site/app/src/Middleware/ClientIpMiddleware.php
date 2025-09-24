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
use Middlewares\ClientIp;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClientIpMiddleware extends Model implements MiddlewareInterface
{
    private ?ClientIp $middleware = null;
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

        // If the ClientIp middleware has been initialized, use it
        if (null !== $this->middleware) {
            return $this->middleware->process($request, $handler);
        }

        // Otherwise pass directly to the next handler
        return $handler->handle($request);
    }

    private function initialize(): void
    {
        // Check conditions to enable ClientIp
        if (!$this->shouldEnable()) {
            return;
        }

        try {
            // Create the ClientIp middleware
            $this->middleware = new ClientIp();

            // Check if proxy configuration is enabled
            if ($this->config->get('debug.clientIp.proxy', false)) {
                $ips = $this->config->get('debug.clientIp.ips', []);
                $headers = $this->config->get('debug.clientIp.headers', []);

                $args = [];
                if (!empty($ips)) {
                    $args[] = $ips;
                }
                if (!empty($headers)) {
                    $args[] = $headers;
                }

                // Apply proxy configuration
                $this->middleware = \call_user_func_array($this->middleware->proxy(...), $args);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize ClientIp middleware: '.$e->getMessage());
        }
    }

    private function shouldEnable(): bool
    {
        // Don't enable in CLI mode
        if ($this->helper->Env()->isCli()) {
            return false;
        }

        // Check if client IP processing is enabled
        $clientIpConfig = $this->config->get('debug.clientIp', true);

        // If it's a boolean, use it directly
        if (\is_bool($clientIpConfig)) {
            return $clientIpConfig;
        }

        // If it's an array, check the 'enabled' key
        if (\is_array($clientIpConfig)) {
            return $this->config->get('debug.clientIp.enabled', true);
        }

        // Default: enabled
        return true;
    }
}
