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
use App\Handler\CustomErrorHandler;
use App\Service\HtmlMinifyService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Error\Renderers\HtmlErrorRenderer;
use Slim\Error\Renderers\JsonErrorRenderer;
use Slim\Error\Renderers\XmlErrorRenderer;
use Slim\Exception\HttpException;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * Wrapper for Slim's ErrorMiddleware that handles configuration internally
 * and renders custom error pages through appropriate controllers based on content type.
 * Use CustomErrorHandler with intelligent logging levels.
 *
 * https://odan.github.io/2020/05/27/slim4-error-handling.html
 * https://dzone.com/articles/a-first-look-at-slim-4
 * https://github.com/juliangut/slim-exception
 * https://github.com/zeuxisoo/php-slim-whoops/
 */
final class SlimErrorMiddleware implements MiddlewareInterface
{
    private ErrorMiddleware $errorMiddleware;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
        private readonly HtmlMinifyService $minifyService
    ) {
        $this->initializeErrorMiddleware();
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $this->errorMiddleware->process($request, $handler);
    }

    private function initializeErrorMiddleware(): void
    {
        $app = $this->container->get(App::class);

        $this->errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            $this->getConfigWithFallback('enabled', false),
            $this->getConfigWithFallback('errors', true),
            $this->getConfigWithFallback('errorDetails', false),
            $this->logger->channel('internal')
        );

        // Use custom error handler with intelligent logging
        $customHandler = new CustomErrorHandler(
            $this->container,  // Pass container for configuration access
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            $this->logger->channel('internal')
        );

        $this->errorMiddleware->setDefaultErrorHandler($customHandler);

        // Register custom error renderers based on configuration
        $this->registerCustomRenderers();
    }

    /**
     * Register custom error renderers based on configuration.
     */
    private function registerCustomRenderers(): void
    {
        $errorHandler = $this->errorMiddleware->getDefaultErrorHandler();

        // Define content types and their configurations
        $contentTypes = [
            'text/html' => $this->container->get('env'),
            'application/json' => 'api',
            'application/xml' => 'xml',
            'text/xml' => 'xml',
        ];

        foreach ($contentTypes as $contentType => $env) {
            if ($this->getConfigWithFallback('custom_errors.'.$contentType, false)) {
                // Create a wrapper renderer that handles fallback internally
                $middleware = $this;
                $renderer = function (\Throwable $exception, bool $displayErrorDetails) use ($middleware, $env, $contentType) {
                    try {
                        return $middleware->renderCustomError($exception, $displayErrorDetails, $env);
                    } catch (\Throwable) {
                        // Fallback to Slim's default renderer for this content type
                        return $middleware->renderWithSlimDefault($exception, $displayErrorDetails, $contentType);
                    }
                };

                $errorHandler->registerErrorRenderer($contentType, $renderer);
            }
        }
    }

    /**
     * Generic method to render custom error through specified controller.
     */
    private function renderCustomError(\Throwable $exception, bool $displayErrorDetails, string $env): string
    {
        try {
            // Determine status code
            $statusCode = $exception instanceof HttpException
                ? $exception->getCode()
                : 500;

            // Create a mock request for the controller
            $request = ServerRequestFactory::createFromGlobals();

            // Create response with status code using App's response factory
            $app = $this->container->get(App::class);
            $response = $app->getResponseFactory()->createResponse($statusCode);

            // Get appropriate controller
            $namespace = ucwords('\\'._NAMESPACE_BASE.'\Controller\Env\\'.$env.'\Controller', '\\');
            $controller = $this->container->get($namespace);

            // Set controller properties for error rendering
            $controller->controller = 'error';
            $controller->action = (string) $statusCode;

            // Add exception details to request if debug details are enabled
            if ($displayErrorDetails) {
                $request = $request->withAttribute('exception_details', [
                    'type' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }

            // Call the controller
            $response = $controller($request, $response, []);

            // Slim's error renderers expect renderable content (HTML, JSON, XML, etc.), not redirect responses.
            // Normal response content can be safely converted to string for the error renderer.
            $htmlContent = $this->isRedirectResponse($response) ? $this->createRedirectHtml($response, $exception) : (string) $response->getBody();

            // Apply HTML minification to error pages using smart minification
            // This automatically detects HTML content and applies minification when appropriate
            if (!empty($htmlContent)) {
                $htmlContent = $this->minifyService->smartMinify($htmlContent, $response);
            }

            return $htmlContent;
        } catch (\Throwable $e) {
            // If custom rendering fails, log with appropriate level and re-throw to let Slim handle it
            $logLevel = $exception instanceof HttpException && $exception->getCode() < 500 ? 'warning' : 'error';

            $this->logger->{$logLevel}('Custom error rendering failed', [
                'error' => $e->getMessage(),
                'original_error' => $exception->getMessage(),
            ]);

            // Re-throw original exception to let Slim's default system handle it
            throw $exception;
        }
    }

    /**
     * Render error using Slim's default renderer for the given content type.
     */
    private function renderWithSlimDefault(\Throwable $exception, bool $displayErrorDetails, string $contentType): string
    {
        // Map content types to Slim's default error renderer classes
        $slimRenderers = [
            'text/html' => HtmlErrorRenderer::class,
            'application/json' => JsonErrorRenderer::class,
            'application/xml' => XmlErrorRenderer::class,
            'text/xml' => XmlErrorRenderer::class,
        ];

        $rendererClass = $slimRenderers[$contentType] ?? HtmlErrorRenderer::class;
        $renderer = new $rendererClass();

        return $renderer($exception, $displayErrorDetails);
    }

    /**
     * Create HTML content that performs a redirect.
     */
    private function createRedirectHtml(ResponseInterface $response, \Throwable $exception): string
    {
        $location = $response->getHeaderLine('Location');
        $title = \sprintf('Error: %s', $exception->getMessage());

        return \sprintf(
            '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0; url=%s">
    <title>%s</title>
</head>
<body>
    <p>Redirecting...</p>
    <p>If you are not redirected automatically, <a href="%s">click here</a>.</p>
    <script>window.location.href = "%s";</script>
</body>
</html>',
            htmlspecialchars($location, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($location, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($location, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Check if response is a redirect (3xx status code).
     */
    private function isRedirectResponse(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        return $statusCode >= 300 && $statusCode < 400;
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    private function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "debug.{$env}",
            'debug',
        ];

        return $this->container->get('config')->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }
}
