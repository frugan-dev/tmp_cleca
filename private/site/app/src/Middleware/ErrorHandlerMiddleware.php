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
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Simple error middleware that only handles PHP error logging with intelligent levels.
 * Prevents email spam by using appropriate log levels for different error types.
 */
final readonly class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ContainerInterface $container,
        private LoggerInterface $logger,
        private HelperInterface $helper
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Check if we should be active
        if (!$this->shouldEnable()) {
            return $handler->handle($request);
        }

        // Set up PHP error handler for logging
        $this->setupErrorHandler();

        try {
            return $handler->handle($request);
        } finally {
            // Restore previous error handler
            restore_error_handler();
        }
    }

    private function shouldEnable(): bool
    {
        // Only active if debug is disabled OR whoops is disabled
        return !$this->getConfigWithFallback('enabled', false)
               || !$this->getConfigWithFallback('whoops.enabled', false);
    }

    private function setupErrorHandler(): void
    {
        $config = $this->container->get('config');
        $errorTypes = E_ALL;

        set_error_handler(
            function (int $errno, string $errstr, string $errfile, int $errline): bool {
                $config = $this->container->get('config');
                $errorLevels = $config->get('logger.error_handler.php_errors_levels', []);

                // Determine the appropriate log level
                $logLevel = $this->determineLogLevel($errno, $errstr, $errorLevels);

                $errorMessage = \sprintf(
                    'Error [%s] -> %s on line %d in file %s',
                    $this->helper->Env()->getErrorTypeByValue($errno),
                    $errstr,
                    $errline,
                    $errfile
                );

                // Try to log with our custom logger
                $logged = false;
                if (!empty($logLevel) && method_exists($this->logger, $logLevel.'Internal')) {
                    try {
                        $this->logger->{$logLevel.'Internal'}('PHP Error Handler', [
                            'error' => $errorMessage,
                            'errno' => $errno,
                            'file' => $errfile,
                            'line' => $errline,
                        ]);

                        $logged = true;
                    } catch (\Throwable) {
                        // Logger failed, will let PHP handle it as fallback
                    }
                }

                /*
                 * Return value controls PHP's internal error handler:
                 * - return true:  suppress PHP's internal error handler (we handled it)
                 * - return false: let PHP's internal error handler run too (fallback logging)
                 *
                 * Strategy: if our custom logging succeeded, suppress PHP's handler.
                 * If our logging failed, let PHP handle it as emergency fallback.
                 */
                return $logged;
            },
            $errorTypes
        );
    }

    /**
     * Determine appropriate log level for PHP errors.
     */
    private function determineLogLevel(int $errno, string $errstr, array $configuredLevels): ?string
    {
        // First check if there's a configured level for this error type
        if (\array_key_exists($errno, $configuredLevels)) {
            return $configuredLevels[$errno];
        }

        return null;
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
