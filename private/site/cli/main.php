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

use App\Factory\Logger\LoggerInterface;
use App\Helper\HelperInterface;
use Slim\Factory\ServerRequestCreatorFactory;

$app = require _ROOT.'/bootstrap.php';

$container = $app->getContainer();

// Create a fake CLI request to trigger middleware but avoid HTTP output
// https://discourse.slimframework.com/t/using-slim-4-app-in-cli-mode/3669
// https://github.com/slimphp/Slim/issues/2710#issuecomment-499267451
$serverRequestCreator = ServerRequestCreatorFactory::create();
$fakeRequest = $serverRequestCreator->createServerRequestFromGlobals()
    ->withMethod('GET')
    ->withRequestTarget('/cli-dummy')
    ->withHeader('CONTENT_TYPE', 'application/x-www-form-urlencoded')
;

// Process through middleware without emitting output
try {
    // Use handle() instead of run() to process middleware without automatic output emission
    $response = $app->handle($fakeRequest);
    // Response is returned but not emitted - middleware initialization is complete
} catch (Exception $e) {
    // If routing fails (expected for dummy route), middleware still ran
    // which is what we want for CLI initialization
    // Let any real errors show through for debugging
}

$container->set(
    'request',
    $fakeRequest
);

$opts = [
    'path' => [
        'short' => 'p',
        'suffix' => ':',
        'descr' => __('Relative directory o file with extension, e.g. "daily" or "enabled/hello.php"'),
    ],
    'mode' => [
        'short' => 'm',
        'suffix' => '::',
        'descr' => __('Alternative modes: "raw"'),
    ],
    'username' => [
        'short' => 'u',
        'suffix' => '::',
        'descr' => __('User\'s username'),
    ],
    'log-level' => [
        'short' => 'l',
        'suffix' => '::',
        'descr' => __('Default monolog level: "debug", "info", etc.'),
    ],
    'stop-if-errors' => [
        'short' => 's',
        'suffix' => '::',
        'descr' => __('Stop if there are some errors: "true" o "false"'),
    ],
    'tid' => [
        'short' => 't',
        'suffix' => '::',
        'descr' => __('Tread ID, only for daemon'),
    ],
    'other' => [
        'short' => 'o',
        'suffix' => '::',
        'descr' => __('Other stuff'),
    ],
    'help' => [
        'short' => 'h',
        'descr' => __('Print this help'),
    ],
];

$cliArgs = $container->get(HelperInterface::class)->Cli()->setCliArgs($opts);

// https://en.wikipedia.org/wiki/Exit_status
$cliReturn = 0;

if ($cliArgs['path'] && false !== $cliArgs['help']) {
    $cliBuffer = '';
    $cliLogLevel = $cliArgs['log-level'] ?: 'info';

    $cliIncludes = [];

    if (is_dir(_ROOT.'/cli/'.$cliArgs['path'])) {
        // https://github.com/docker-library/php/issues/719
        $cliIncludes = \Safe\glob(_ROOT.'/cli/'.$cliArgs['path'].'/*.php');
    } elseif (file_exists(_ROOT.'/cli/'.$cliArgs['path'])) {
        $cliIncludes[] = _ROOT.'/cli/'.$cliArgs['path'];
    }

    if ((is_countable($cliIncludes) ? count($cliIncludes) : 0) > 0) {
        if ('raw' === $cliArgs['mode']) {
            Kint::$enabled_mode = true;
        }

        foreach ($cliIncludes as $cliInclude) {
            $container->set('errors', []);

            if ('raw' === $cliArgs['mode']) {
                $cliReturn = include_once $cliInclude;
            } else {
                printf(__('Processing %1$s').PHP_EOL, basename((string) $cliInclude)).'...';

                \Safe\ob_start();

                $cliReturn = include_once $cliInclude;

                $cliBuffer .= sprintf(__('%1$s output: %2$s').PHP_EOL, basename((string) $cliInclude), $cliReturn);

                $cliBuffer .= ob_get_contents();
                \Safe\ob_end_clean();

                $cliBuffer .= '------------------------------------------------------'.PHP_EOL;
            }

            if (0 !== $cliReturn) {
                if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
                    $cliLogLevel = 'error';

                    if (true === (bool) $cliArgs['stop-if-errors']) {
                        break;
                    }
                }

                if (64 === $cliReturn) {
                    break;
                }
            }
        }

        if ('raw' === $cliArgs['mode']) {
            Kint::$enabled_mode = $container->get('config')['debug.enabled'];
        }
    }

    if (!empty($cliBuffer)) {
        // When using named parameter, the parameter of the called class must be used, and parameter name of the parent class/classes are not considered.
        $message = sprintf(
            __('%1$s %2$s report - %3$s', null, $container->get('config')['logger.locale']),
            basename(__FILE__, '.php'),
            $cliArgs['path'],
            $_SERVER['APP_ENV'] ?? null
        );
        $logged = false;

        if (!empty($cliLogLevel) && method_exists($container->get(LoggerInterface::class), $cliLogLevel)) {
            try {
                $container->get(LoggerInterface::class)->{$cliLogLevel}($message, [
                    'text' => $cliBuffer,
                ]);

                $logged = true;
            } catch (Throwable) {
                // Custom logger failed, will fallback to error_log
            }
        }

        // Fallback to error_log if custom logging failed or level not configured
        if (!$logged) {
            error_log(basename(__FILE__, '.php').": {$message}");
        }
    }
} else {
    echo $container->get(HelperInterface::class)->Cli()->getCliHelp($opts);
}

// https://github.com/phpro/grumphp/blob/master/doc/tasks/phpparser.md#no_exit_statements
exit($cliReturn);
