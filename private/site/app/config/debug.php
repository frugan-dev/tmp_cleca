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

$debugWhitelistIps = explode(',', (string) $_ENV['DEBUG_WHITELIST_IPS'] ?? '');
$debugEnabled = in_array(getClientIp(), $debugWhitelistIps, true) ? true : false;

return [
    'enabled' => $debugEnabled,

    'whitelist.ips' => $debugWhitelistIps,

    'errors' => true,
    'errorDetails' => true,

    'emailsTo' => [
        'dev@frugan.it' => 'Frugan',
    ],

    'ips' => [
    ],

    // https://github.com/middlewares/client-ip
    // https://adam-p.ca/blog/2022/03/x-forwarded-for/
    'clientIp.proxy' => true,

    // It requires clientIp.proxy = true
    // Array of ips or cidr range of the trusted proxies.
    // If it's empty, detection use proxies, but no ip filtering is made.
    'clientIp.ips' => [
        '10.0.0.0/16',
        '172.0.0.0/8',
    ],

    // It requires clientIp.proxy = true
    // List of the headers to inspect.
    // If it's not defined, uses the default value.
    'clientIp.headers' => [
        // custom
        'CF-Connecting-IP', // Cloudflare
        'X-Real-Ip', // Traefik, Nginx
        'True-Client-IP', // Cloudflare, Akamai

        // default
        'Forwarded',
        'Forwarded-For',
        'X-Forwarded',
        'X-Forwarded-For',
        'X-Cluster-Client-Ip',
        'Client-Ip',
    ],

    // https://github.com/middlewares/debugbar
    'debugbar.enabled' => false, // it can slow down response..
    'debugbar.captureAjax' => false, // it can slow down response..
    'debugbar.inline' => false,

    // https://github.com/middlewares/whoops
    'whoops.enabled' => $debugEnabled,
    'whoops.catchErrors' => $debugEnabled,

    'custom_errors' => [
        'text/html' => true,
        'application/json' => false,
        'application/xml' => false,
        'text/xml' => false,
    ],
    // 'front.custom_errors.text/html' => true,
];
