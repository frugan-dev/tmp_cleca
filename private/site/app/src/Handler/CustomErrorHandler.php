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

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler;
use Slim\Interfaces\CallableResolverInterface;

/**
 * Custom Error Handler with intelligent logging levels and optimized log structure.
 *
 * This handler prevents email spam by using appropriate log levels:
 * - 4xx errors (client errors) are logged as 'warning' or 'info'
 * - 5xx errors (server errors) are logged as 'error' (will send emails)
 * - Non-HTTP exceptions are logged as 'error'
 *
 * It also optimizes log messages by separating error details into context
 * instead of concatenating everything into a single long message.
 */
class CustomErrorHandler extends ErrorHandler
{
    public function __construct(
        private readonly ContainerInterface $container,
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($callableResolver, $responseFactory, $logger);
    }

    /**
     * Override the logError method to use intelligent logging levels and optimized structure.
     */
    #[\Override]
    protected function logError(string $error): void
    {
        // Determine the appropriate log level based on error type
        $logLevel = $this->determineLogLevel($this->exception, $this->statusCode);

        // Create concise message
        $message = $this->buildErrorMessage($this->exception);

        // Create structured context instead of long concatenated message
        $context = $this->buildErrorContext($this->exception);

        // Log with the determined level and structured context
        $this->logger->{$logLevel}($message, $context);
    }

    /**
     * Determine the appropriate log level based on exception type and status code.
     */
    protected function determineLogLevel(\Throwable $exception, int $statusCode): string
    {
        $config = $this->container->get('config');

        // Get configured status code to level mapping
        $statusCodeLevels = $config->get('logger.error_handler.http_status_levels', []);
        if (isset($statusCodeLevels[$statusCode])) {
            return $statusCodeLevels[$statusCode];
        }

        // Check default levels for ranges
        $defaultLevels = $config->get('logger.error_handler.http_default_levels', []);
        if ($statusCode >= 400 && $statusCode < 500 && isset($defaultLevels['4xx'])) {
            return $defaultLevels['4xx'];
        }
        if ($statusCode >= 500 && isset($defaultLevels['5xx'])) {
            return $defaultLevels['5xx'];
        }

        // Fallback to original logic if no config available
        if ($exception instanceof HttpException) {
            return match (true) {
                $exception instanceof HttpNotFoundException => 'info',
                $exception instanceof HttpBadRequestException => 'warning',
                $exception instanceof HttpUnauthorizedException => 'warning',
                $exception instanceof HttpForbiddenException => 'warning',
                $exception instanceof HttpMethodNotAllowedException => 'warning',
                $statusCode >= 400 && $statusCode < 500 => 'warning',
                $statusCode >= 500 => 'error',
                default => 'warning'
            };
        }

        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            default => 'error'
        };
    }

    /**
     * Build a concise, readable error message for the log.
     */
    protected function buildErrorMessage(\Throwable $exception): string
    {
        $type = $this->getExceptionType($exception);
        $message = $exception->getMessage();

        // Create a concise but informative message
        if ($exception instanceof HttpException) {
            return \sprintf(
                'HTTP %d %s: %s',
                $this->statusCode,
                $type,
                $message ?: 'No message'
            );
        }

        return \sprintf(
            '%s: %s',
            $type,
            $message ?: 'No message'
        );
    }

    /**
     * Build structured context array for better log analysis.
     */
    protected function buildErrorContext(\Throwable $exception): array
    {
        $context = [
            'exception_type' => $exception::class,
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'status_code' => $this->statusCode,
        ];

        // Add HTTP-specific context
        if ($exception instanceof HttpException) {
            $context['http_exception'] = true;

            if ($exception instanceof HttpMethodNotAllowedException) {
                $context['allowed_methods'] = $exception->getAllowedMethods();
            }
        }

        // Add request context if available
        if (isset($this->request)) {
            $context['request'] = [
                'method' => $this->request->getMethod(),
                'uri' => (string) $this->request->getUri(),
                'headers' => $this->sanitizeHeaders($this->request->getHeaders()),
            ];
        }

        // Add trace only for error level (to avoid cluttering warning/info logs)
        if ($this->logErrorDetails && 'error' === $this->determineLogLevel($exception, $this->statusCode)) {
            $context['trace'] = $this->formatTrace($exception);
        }

        return $context;
    }

    /**
     * Get a human-readable exception type name.
     */
    protected function getExceptionType(\Throwable $exception): string
    {
        $className = $exception::class;

        // Remove namespace for readability
        $parts = explode('\\', $className);
        $shortName = end($parts);

        // Convert CamelCase to readable format
        return \Safe\preg_replace('/([A-Z])/', ' $1', $shortName);
    }

    /**
     * Format stack trace in a more readable way.
     */
    protected function formatTrace(\Throwable $exception): array
    {
        $trace = $exception->getTrace();
        $formattedTrace = [];

        foreach (\array_slice($trace, 0, 10) as $i => $frame) { // Limit to top 10 frames
            $formattedTrace[] = \sprintf(
                '#%d %s%s%s() called at [%s:%d]',
                $i,
                $frame['class'] ?? '',
                $frame['type'] ?? '',
                $frame['function'] ?? 'unknown',
                $frame['file'] ?? 'unknown',
                $frame['line'] ?? 0
            );
        }

        return $formattedTrace;
    }

    /**
     * Sanitize headers to remove sensitive information.
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token'];
        $sanitized = [];

        foreach ($headers as $name => $values) {
            $lowerName = strtolower((string) $name);
            if (\in_array($lowerName, $sensitiveHeaders, true)) {
                $sanitized[$name] = ['*** REDACTED ***'];
            } else {
                $sanitized[$name] = $values;
            }
        }

        return $sanitized;
    }
}
