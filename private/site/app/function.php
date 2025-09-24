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

use Psr\Container\ContainerInterface;

if (!function_exists('getEnvironment')) {
    function getEnvironment(?ContainerInterface $container = null): string
    {
        static $env;

        if (!empty($env)) {
            return $env;
        }

        $env = 'front';

        if (PHP_SAPI === 'cli') {
            $env = 'cli';
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $config = $container?->get('config');
            $uri = $_SERVER['REQUEST_URI'];
            $authPath = $config?->get('auth.path');

            if (str_starts_with((string) $uri, '/api/')) {
                $env = 'api';
            } elseif ($authPath && str_starts_with((string) $uri, '/'.$authPath)) {
                $env = 'back';
            } elseif (str_ends_with((string) $uri, '.js')) {
                $env = 'js';
            } elseif (str_ends_with((string) $uri, '.xml')) {
                $env = 'xml';
            }

            if ($config?->get('debug.enabled')) {
                \Safe\error_log("REQUEST_URI={$_SERVER['REQUEST_URI']}, env={$env}");
            }
        }

        return $env;
    }
}

if (!function_exists('getClientIp')) {
    // https://adam-p.ca/blog/2022/03/x-forwarded-for/
    // https://developers.cloudflare.com/support/troubleshooting/restoring-visitor-ips/restoring-original-visitor-ips/
    // https://developers.cloudflare.com/fundamentals/reference/http-request-headers/
    // https://snicco.io/blog/how-to-safely-get-the-ip-address-in-a-wordpress-plugin
    // https://snicco.io/vulnerability-disclosure/wordfence/dos-through-ip-spoofing-wordfence-7-6-2
    // https://stackoverflow.com/a/2031935/3929620
    // https://stackoverflow.com/a/58239702/3929620
    function getClientIp()
    {
        $ip = '';

        foreach ([
            'REMOTE_ADDR', // The only truly reliable one if there are no proxies

            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP', // Traefik, Nginx
            'HTTP_TRUE_CLIENT_IP', // Cloudflare, Akamai

            // Less reliable headers, easily spoofed
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_X_CLUSTER_CLIENT_IP',
        ] as $key) {
            if (!array_key_exists($key, $_SERVER)) {
                continue;
            }

            // For headers with IP lists, we take the first non-private IP from the beginning
            // (X-Forwarded-For is in order client -> proxy1 -> proxy2)
            $ips = array_map('trim', explode(',', (string) $_SERVER[$key]));

            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $ip;
    }
}

if (!function_exists('getBytes')) {
    // https://www.php.net/manual/en/function.ini-get.php
    // https://stackoverflow.com/a/46320238/3929620
    // https://stackoverflow.com/a/44767616/3929620
    function getBytes($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        \Safe\preg_match('/^(?<value>\d+)(?<option>[K|M|G]*)$/i', $value, $matches);

        $value = (int) $matches['value'];
        $option = strtoupper((string) $matches['option']);

        if ($option) {
            if ('K' === $option) {
                $value *= 1024;
            } elseif ('M' === $option) {
                $value *= 1024 * 1024;
            } elseif ('G' === $option) {
                $value *= 1024 * 1024 * 1024;
            }
        }

        return $value;
    }
}
