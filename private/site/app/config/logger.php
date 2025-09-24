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

use Monolog\Level;

/*
 * MONOLOG LOGGER CONFIGURATION
 *
 * === LOG LEVELS REFERENCE ===
 * DEBUG (100): Detailed debug information.
 * INFO (200): Interesting events. Examples: User logs in, SQL logs.
 * NOTICE (250): Normal but significant events.
 * WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
 * ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
 * CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
 * ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
 * EMERGENCY (600): Emergency: system is unusable.
 *
 * === LOG LEVEL NOTATION GUIDE ===
 * This configuration uses two different notations for log levels:
 *
 * 1. MONOLOG LEVEL OBJECTS (Level::Error, Level::Debug, etc.)
 *    Used in: handlers.*.level, channels.*.handlers.*.level
 *    Reason: These values are passed DIRECTLY to Monolog handler constructors
 *            which expect Level enum objects, not strings.
 *    Example: new SymfonyMailerHandler($mailer, $message, Level::Error)
 *
 * 2. PSR-3 LEVEL STRINGS ('error', 'warning', 'info', etc.)
 *    Used in: error_handler.* configurations
 *    Reason: These values are processed by our custom error handling logic
 *            and used to call PSR-3 logger methods dynamically.
 *    Example: $this->logger->{$logLevel}($message) where $logLevel = 'error'
 *
 * This distinction ensures:
 * - No unnecessary conversions (Level::Error->toPsrLogLevel() or Logger::toMonologLevel(Level::Error)->toPsrLogLevel())
 * - Type safety (Monolog gets Level objects, PSR-3 calls get strings)
 * - Performance (direct usage without conversion overhead)
 * - Maintainability (clear separation of concerns)
 *
 * PROCESSORS vs HANDLERS CONFIGURATION LOGIC
 *
 * === PROCESSORS: "À la Carte" Approach ===
 * - default_processors: Used ONLY if channel has no specific processors config
 * - channels.{name}.processors: COMPLETELY replaces default_processors for that channel
 * - If you want defaults + extras, you must list them explicitly
 *
 * Example:
 * default_processors: ['psr_message', 'web']
 * security.processors: ['psr_message', 'introspection'] → Security gets ONLY these two
 * app channel (no processors config) → Gets the default ['psr_message', 'web']
 *
 * === HANDLERS: "Base + Extensions" Approach ===
 * - default_handlers: Always added to ALL channels (minimum requirement)
 * - channels.{name}.handlers.{type}: Customizes handler configuration (level, path, etc.)
 * - Additional handlers: Added programmatically via factory methods (database, sentry, email)
 *
 * Example:
 * default_handlers: ['file'] → All channels get file logging
 * security.handlers.file.level: Level::Error → Security channel customizes file level
 * + addEmailHandler('security') → Security also gets email notifications
 *
 * === RATIONALE ===
 * Handlers: All channels need to write logs somewhere (file = minimum safety net)
 * Processors: Different channels have different context needs (flexibility over consistency)
 */

return [
    // Default processors created automatically
    // Applied only if channel doesn't specify its own processors
    'default_processors' => ['psr_message'],

    // Global processors configurations (can be overridden per channel)
    'processors' => [
        'psr_message' => [
            // Always enabled for placeholder substitution
        ],
        // 'web' => [
        //     'extra_fields' => null, // null means default fields
        // ],
        // Uncomment to enable additional processors
        // 'introspection' => [
        //     'level' => Level::Error,
        //     'skip_classes' => [],
        // ],
        // 'memory_usage' => [
        //     'real_usage' => true,
        //     'use_formatting' => true,
        // ],
        // 'memory_peak' => [
        //     'real_usage' => true,
        //     'use_formatting' => true,
        // ],
        // 'process_id' => [
        // ],
        // 'git' => [
        //     'level' => Level::Debug,
        //     'path' => null,
        // ],
    ],

    // Default handlers created automatically
    // Always applied to all channels (minimum logging requirement)
    'default_handlers' => ['file'],

    // Global handlers configurations (can be overridden per channel)
    'handlers' => [
        'file' => [
            'level' => Level::Debug,
            'path' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/logs/%s.log'),
            'max_files' => 300,
            // https://stackoverflow.com/a/28672464/3929620
            'permissions' => 0o777,
        ],
        'database' => [
            'level' => Level::Info,
        ],
        'sentry' => [
            'level' => Level::Error,
        ],
        'email' => [
            'level' => Level::Error,
            'dedup_path' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/logs/dedup-%s.log'),
            'dedup_level' => Level::Error,
            'dedup_time' => 86400,
        ],
    ],

    'channels' => [
        'app' => [
        ],
        'internal' => [
            'handlers' => [
                'file' => [
                    'level' => Level::Info,
                ],
                'email' => [
                    'level' => Level::Error,
                    'dedup_path' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/logs/dedup-%s.log'),
                    'dedup_level' => Level::Error,
                    'dedup_time' => 86400,
                ],
            ],
        ],
    ],

    // https://www.php.net/manual/en/errorfunc.constants.php
    'error_handler' => [
        // Map HTTP status codes to log levels (for SlimErrorMiddleware/CustomErrorHandler)
        'http_status_levels' => [
            400 => 'warning',  // Bad Request
            401 => 'warning',  // Unauthorized
            403 => 'warning',  // Forbidden
            404 => 'info',     // Not Found
            405 => 'warning',  // Method Not Allowed
            406 => 'info',     // Not Acceptable
            422 => 'warning',  // Unprocessable Entity
            429 => 'warning',  // Too Many Requests
            500 => 'error',    // Internal Server Error
            502 => 'error',    // Bad Gateway
            503 => 'error',    // Service Unavailable
            504 => 'error',    // Gateway Timeout
        ],

        // Default levels for HTTP status ranges (for SlimErrorMiddleware/CustomErrorHandler)
        'http_default_levels' => [
            '4xx' => 'warning',  // Client errors - not our fault
            '5xx' => 'error',    // Server errors - our fault, need attention
        ],

        // PHP error type mappings (for ErrorHandlerMiddleware)
        'php_errors_levels' => [
            E_PARSE => 'error',
            E_ERROR => 'error',
            E_RECOVERABLE_ERROR => 'error',
            E_COMPILE_ERROR => 'error',
            E_CORE_ERROR => 'error',
            E_USER_ERROR => 'error',
            E_WARNING => null, // warning
            E_COMPILE_WARNING => 'warning',
            E_CORE_WARNING => 'warning',
            E_USER_WARNING => 'warning',
            E_NOTICE => 'warning',
            E_USER_NOTICE => 'warning',
            E_STRICT => 'warning',
            E_DEPRECATED => null, // warning
            E_USER_DEPRECATED => 'warning',
        ],
    ],

    // twbs 5
    'levels' => [
        'debug' => [
            'color' => '6c757d',
        ],
        'info' => [
            'color' => '198754',
        ],
        'notice' => [
            'color' => '0dcaf0',
        ],
        'warning' => [
            'color' => 'ffc107',
        ],
        'error' => [
            'color' => 'dc3545',
        ],
        'critical' => [
            'color' => 'dc3545',
        ],
        'alert' => [
            'color' => 'dc3545',
        ],
        'emergency' => [
            'color' => 'dc3545',
        ],
    ],

    'mailer.level' => 'debug',

    'locale' => 'en_US.UTF-8',
];
