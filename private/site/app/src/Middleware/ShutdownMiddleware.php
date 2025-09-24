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
use Middlewares\Shutdown;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Shutdown middleware that terminates ALL requests when maintenance is enabled.
 * Must be placed early in the middleware pipeline to have precedence over routing and errors.
 */
class ShutdownMiddleware extends Model implements MiddlewareInterface
{
    protected static string $env = 'shutdown';

    protected string $mimeType = 'text/html';

    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
        protected LoggerInterface $logger
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Early check - if not enabled, pass through immediately
        if (!$this->isShutdownEnabled($request)) {
            return $handler->handle($request);
        }

        try {
            // Set appropriate content type based on request
            $this->setMimeType($request);

            // Shutdown is enabled - terminate the request with 503
            $shutdown = new Shutdown();

            // Configure retry-after if specified
            $this->configureRetryAfter($shutdown);

            // Set custom renderer if this class has one
            if (method_exists($this, 'renderCallback') && \is_callable($this->renderCallback(...))) {
                $shutdown->render($this->renderCallback(...));
            }

            // This will return 503 and NOT call $handler->handle()
            $response = $shutdown->process($request, $handler);

            return $response->withHeader('Content-Type', $this->mimeType);
        } catch (\Throwable $e) {
            $this->logger->error('Shutdown middleware failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback: show basic maintenance message
            return $this->createFallbackResponse($request);
        }
    }

    /**
     * Default render callback - override in environment-specific classes.
     */
    public function renderCallback(ServerRequestInterface $request): string
    {
        return $this->getBasicMaintenanceMessage();
    }

    /**
     * Detect appropriate content type based on request.
     */
    protected function setMimeType(ServerRequestInterface $request): string
    {
        // Check Accept header
        $acceptHeader = $request->getHeaderLine('Accept');

        if (empty($acceptHeader) || '*/*' === $acceptHeader) {
            return $this->mimeType;
        }

        // Parse Accept header priorities
        $mimeTypes = [
            'application/json' => ['application/json', 'application/*'],
            'application/xml' => ['application/xml', 'text/xml'],
            'application/javascript' => ['application/javascript', 'text/javascript'],
            'text/html' => ['text/html', 'text/*'],
        ];

        foreach ($mimeTypes as $mimeType => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($acceptHeader, $pattern)) {
                    $this->mimeType = $mimeType;

                    break;
                }
            }
        }

        return $this->mimeType;
    }

    /**
     * Check if shutdown is enabled for this environment and request.
     */
    protected function isShutdownEnabled(ServerRequestInterface $request): bool
    {
        // Check if shutdown is globally enabled
        $enabled = $this->getConfigWithFallback('enabled', false);
        if (!$enabled) {
            return false;
        }

        // Check IP whitelist
        $clientIp = $request->getAttribute('client-ip');
        if ($this->isIpWhitelisted($clientIp)) {
            return false;
        }

        return true;
    }

    /**
     * Check if client IP is in whitelist.
     */
    protected function isIpWhitelisted(?string $clientIp): bool
    {
        if (empty($clientIp)) {
            return false;
        }

        $whitelistIps = $this->getConfigWithFallback('whitelist.ips', []);

        if (empty($whitelistIps) || !\is_array($whitelistIps)) {
            return false;
        }

        return \in_array($clientIp, $whitelistIps, true);
    }

    /**
     * Configure retry-after header on shutdown middleware.
     */
    protected function configureRetryAfter(Shutdown $shutdown): void
    {
        $retryAfter = $this->getConfigWithFallback('retryAfter');

        if (empty($retryAfter)) {
            return;
        }

        try {
            // If it's already a timestamp/integer
            if (is_numeric($retryAfter)) {
                $shutdown->retryAfter((int) $retryAfter);

                return;
            }

            // Try to parse as date
            $retryDate = $this->helper->Carbon()->create($retryAfter);
            if ($retryDate) {
                $shutdown->retryAfter($retryDate);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Invalid retryAfter configuration', [
                'retryAfter' => $retryAfter,
                'error' => $e->getMessage(),
            ]);
        }
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
            "shutdown.{$env}",
            'shutdown',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    /**
     * Create fallback response when shutdown middleware fails.
     */
    protected function createFallbackResponse(ServerRequestInterface $request): ResponseInterface
    {
        $factory = $this->container->get(ResponseFactoryInterface::class);
        $response = $factory->createResponse(503);

        $body = $this->getBasicMaintenanceMessage();

        $response->getBody()->write($body);

        return $response->withHeader('Content-Type', $this->mimeType);
    }

    /**
     * Get basic maintenance message for fallback.
     */
    protected function getBasicMaintenanceMessage(): string
    {
        return match ($this->mimeType) {
            'application/json' => '{"error":"System under maintenance","status":503}',
            'application/xml', 'text/xml' => '<?xml version="1.0"?><error><message>System under maintenance</message><status>503</status></error>',
            'application/javascript' => 'console.error("System under maintenance");',
            default => '<!DOCTYPE html><html><head><title>Maintenance</title></head><body><h1>System under maintenance</h1><p>Please try again later.</p></body></html>'
        };
    }
}
