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
use App\Factory\Translator\EventListener\DynamicTranslationListener;
use App\Factory\Translator\EventListener\MissingTranslationListener;
use App\Factory\Translator\TranslatorInterface;
use App\Helper\HelperInterface;
use Slim\App;

return static function (App $app): void {
    $container = $app->getContainer();

    // https://timelord.nl/wordpress/en/software/english-how-to-avoid-global-variables-in-php.html
    // http://wiki.c2.com/?SingletonsAreEvil
    // https://stackoverflow.com/a/138012/3929620
    // https://www.yegor256.com/2016/06/27/singletons-must-die.html
    if (!function_exists('getContainerInstance')) {
        function getContainerInstance()
        {
            static $containerInstance;

            if (!empty($containerInstance)) {
                return $containerInstance;
            }

            global $container;

            return $containerInstance = $container;
        }
    }

    if (!function_exists('sd')) {
        function sd(): void
        {
            call_user_func_array('s', func_get_args());

            if (is_object($container = getContainerInstance())) {
                $config = $container->get('config');

                if (!empty($config->get('debug.enabled'))) {
                    // https://github.com/phpro/grumphp/blob/master/doc/tasks/phpparser.md#no_exit_statements
                    exit;
                }
            }
        }
    }

    if (!function_exists('se')) {
        function se(): void
        {
            global $cliArgs;

            $args = func_get_args();

            if (isCli()) {
                if (isset($cliArgs['mode']) && 'raw' === $cliArgs['mode']) {
                    call_user_func_array('s', $args);
                } elseif (!empty($args)) {
                    foreach ($args as $arg) {
                        if (is_string($arg)) {
                            echo $arg.PHP_EOL;
                        } else {
                            echo var_export($arg, true).PHP_EOL;
                        }
                    }
                }
            } else {
                call_user_func_array('s', $args);
            }
        }
    }

    if (!function_exists('isDev')) {
        function isDev()
        {
            $container = getContainerInstance();

            return call_user_func_array([$container->get(HelperInterface::class)->Env(), __FUNCTION__], func_get_args());
        }
    }

    if (!function_exists('isCli')) {
        function isCli()
        {
            $container = getContainerInstance();

            return call_user_func_array([$container->get(HelperInterface::class)->Env(), __FUNCTION__], func_get_args());
        }
    }

    if (!function_exists('settingOrConfig')) {
        function settingOrConfig()
        {
            $container = getContainerInstance();

            return call_user_func_array([$container->get(HelperInterface::class)->Env(), __FUNCTION__], func_get_args());
        }
    }

    if (!function_exists('version')) {
        function version()
        {
            $container = getContainerInstance();

            return call_user_func_array([$container->get(HelperInterface::class)->Env(), __FUNCTION__], func_get_args());
        }
    }

    if (!function_exists('isBlank')) {
        function isBlank()
        {
            $container = getContainerInstance();

            return call_user_func_array([$container->get(HelperInterface::class)->Strings(), __FUNCTION__], func_get_args());
        }
    }

    // Since Symfony Translation doesn't have built-in missing and dynamic translation events,
    // we handle this through the global translation functions (__(), _n(), etc.)
    if (!function_exists('__')) {
        // $message, $context = null, $locale = null, $domain = null
        function __()
        {
            $args = func_get_args();
            $message = $args[0] ?? '';

            // Always ensure we have a fallback message
            if (empty($message)) {
                return ''; // Return empty string for empty input
            }

            if (is_object($container = getContainerInstance())) {
                if ($container->has(TranslatorInterface::class)) {
                    try {
                        $translator = $container->get(TranslatorInterface::class);
                        $context = $args[1] ?? null;
                        $locale = $args[2] ?? null;
                        $domain = $args[3] ?? null;

                        // Handle dynamic translations
                        if ($container->has(DynamicTranslationListener::class)) {
                            $listener = $container->get(DynamicTranslationListener::class);
                            $listener->handleDynamicTranslation($message, $context, $locale, $domain);
                        }

                        // Get translation
                        $translated = $translator->translate($message, $context, $locale, $domain);

                        // CRITICAL: Check if translation is empty or unchanged
                        if (empty(trim((string) $translated)) || $translated === $message) {
                            // Handle missing translation - let the listener analyze the context
                            if ($container->has(MissingTranslationListener::class)) {
                                $listener = $container->get(MissingTranslationListener::class);
                                $listener->handleMissingTranslation($message, $context, $locale, $domain);
                            }

                            // Return original message if translation is empty
                            return $message;
                        }

                        return $translated;
                    } catch (Exception $e) {
                        if ($container->has(LoggerInterface::class)) {
                            try {
                                $container->get(LoggerInterface::class)->warning(__FUNCTION__.' -> '.__LINE__, [
                                    'error' => $e->getMessage(),
                                    'message' => $message,
                                ]);
                            } catch (Exception) {
                                // Ignore logger errors to avoid infinite loops
                            }
                        }

                        // Always return the original message on error
                        return $message;
                    }
                }
            }

            // Fallback: always return the original message
            return $message;
        }
    }

    if (!function_exists('_e')) {
        function _e(): void
        {
            echo call_user_func_array('__', func_get_args());
        }
    }

    if (!function_exists('_n')) {
        // $singular, $plural, $number, $context = null, $locale = null, $domain = null
        function _n()
        {
            $args = func_get_args();
            $singular = $args[0] ?? '';
            $plural = $args[1] ?? '';
            $number = $args[2] ?? 0;

            // Always ensure we have fallback values
            if (empty($singular) && empty($plural)) {
                return ''; // Return empty string for empty input
            }

            $originalResult = $number <= 1 ? $singular : $plural;

            if (is_object($container = getContainerInstance())) {
                if ($container->has(TranslatorInterface::class)) {
                    try {
                        $translator = $container->get(TranslatorInterface::class);

                        if (empty($singular) || empty($plural) || !isset($args[2])) {
                            // Log warning but don't throw exception
                            if ($container->has(LoggerInterface::class)) {
                                try {
                                    $container->get(LoggerInterface::class)->warning(__FUNCTION__.' expects   singular, plural, and number arguments', [
                                        'args' => $args,
                                        'singular' => $singular,
                                        'plural' => $plural,
                                        'number' => $number,
                                    ]);
                                } catch (Exception) {
                                    // Ignore logger errors
                                }
                            }

                            return $originalResult;
                        }

                        $number = (int) $number;
                        $context = $args[3] ?? null;
                        $locale = $args[4] ?? null;
                        $domain = $args[5] ?? null;

                        // Handle dynamic translations
                        if ($container->has(DynamicTranslationListener::class)) {
                            $listener = $container->get(DynamicTranslationListener::class);
                            $listener->handleDynamicTranslation($singular, $context, $locale, $domain);
                            if ($singular !== $plural) {
                                $listener->handleDynamicTranslation($plural, $context, $locale, $domain);
                            }
                        }

                        // Get translation
                        $translated = $translator->translatePlural($singular, $plural, $number, $context, $locale, $domain);

                        // CRITICAL: Check if translation is empty
                        if (empty(trim((string) $translated))) {
                            // Handle missing translation
                            if ($container->has(MissingTranslationListener::class)) {
                                $listener = $container->get(MissingTranslationListener::class);
                                $listener->handleMissingTranslation($singular, $context, $locale, $domain, true, [
                                    'singular' => $singular,
                                    'plural' => $plural,
                                    'number' => $number,
                                ]);
                            }

                            // Return original result if translation is empty
                            return $originalResult;
                        }

                        return $translated;
                    } catch (Exception $e) {
                        if ($container->has(LoggerInterface::class)) {
                            try {
                                $container->get(LoggerInterface::class)->warning(__FUNCTION__.' -> '.__LINE__, [
                                    'error' => $e->getMessage(),
                                    'singular' => $singular,
                                    'plural' => $plural,
                                    'number' => $number,
                                ]);
                            } catch (Exception) {
                                // Ignore logger errors
                            }
                        }

                        // Always return the original result on error
                        return $originalResult;
                    }
                }
            }

            // Fallback: return appropriate form based on number
            return $originalResult;
        }
    }

    if (!function_exists('_en')) {
        function _en(): void
        {
            echo call_user_func_array('_n', func_get_args());
        }
    }

    if (!function_exists('_x')) {
        function _x()
        {
            // _x() is for contextual translations - it's the same as __() but with explicit context
            return call_user_func_array('__', func_get_args());
        }
    }

    if (!function_exists('_ex')) {
        function _ex()
        {
            return call_user_func_array('_e', func_get_args());
        }
    }

    if (!function_exists('_nx')) {
        function _nx()
        {
            // _nx() is for contextual plural translations - it's the same as _n() but with explicit context
            return call_user_func_array('_n', func_get_args());
        }
    }

    if (!function_exists('_enx')) {
        function _enx()
        {
            return call_user_func_array('_en', func_get_args());
        }
    }

    // http://php.net/manual/en/function.array-key-first.php#123268
    if (!function_exists('array_key_first')) {
        /**
         * Polyfill for array_key_first() function added in PHP 7.3.
         *
         * Get the first key of the given array without affecting
         * the internal array pointer.
         *
         * @param mixed $array An array
         *
         * @return mixed the first key of array if the array is not empty; NULL otherwise
         */
        function array_key_first(mixed $array)
        {
            $key = null;

            if (is_array($array)) {
                foreach ($array as $key => $value) {
                    break;
                }
            }

            return $key;
        }
    }

    // http://php.net/manual/en/function.array-key-last.php#123269
    if (!function_exists('array_key_last')) {
        /**
         * Polyfill for array_key_last() function added in PHP 7.3.
         *
         * Get the last key of the given array without affecting
         * the internal array pointer.
         *
         * @param array $array An array
         *
         * @return mixed the last key of array if the array is not empty; NULL otherwise
         */
        function array_key_last($array)
        {
            $key = null;

            if (is_array($array)) {
                $key = array_key_last($array);
            }

            return $key;
        }
    }
};
