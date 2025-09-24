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

/*
 * https://webmasters.googleblog.com/2011/01/how-to-deal-with-planned-site-downtime.html
 *
 * Returning a 503 HTTP result code can be a great solution for a number of other situations.
 * We encounter a lot of problems with sites that return 200 (OK) result codes for
 * server errors, downtime, bandwidth-overruns or for temporary placeholder pages (“Under Construction”).
 * The 503 HTTP result code is the webmaster’s solution of choice for all these situations.
 */

return [
    // https://github.com/middlewares/shutdown
    'enabled' => $_ENV['SHUTDOWN_ENABLED'],

    'whitelist.ips' => explode(',', (string) $_ENV['SHUTDOWN_WHITELIST_IPS'] ?? ''),

    // date string or false
    'retryAfter' => \Safe\date('Y-m-d', \Safe\strtotime('+2 weeks')),

    'page.file' => 'bg.html',

    'page.vars' => [
        // https://coolbackgrounds.io/white-background/
        // https://www.reddit.com/r/unsplash/comments/s13x4h/comment/i9d63sc/
        // https://changelog.unsplash.com/deprecations/2021/11/25/source-deprecation.html
        // https://wordpress.org/support/topic/i-wouldnt-recommend-using-this/
        // https://make.wordpress.org/themes/handbook/review/resources/#recommended-websites-for-images
        // https://api.unsplash.com/photos/{PHOTO_ID}/?client_id={ACCESS_KEY}
        '{bgObj}' => "[
            {
                src: 'https://images.unsplash.com/photo-1485161467312-b176cc64dacc?".http_build_query([
            'ixid' => 'MnwxODQzMDl8MHwxfGFsbHx8fHx8fHx8fDE2NjMwNzk4NTI',
            'auto' => 'compress,enhance,format',
            'crop' => 'edges',
            'fit' => 'crop',
            'fm' => 'jpg',
            'w' => 1200,
            'h' => 900,
        ])."',
            },
            {
                src: 'https://images.unsplash.com/photo-1558346648-9757f2fa4474?".http_build_query([
            'ixid' => 'MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8',
            'auto' => 'compress,enhance,format',
            'crop' => 'edges',
            'fit' => 'crop',
            'fm' => 'jpg',
            'w' => 1200,
            'h' => 900,
        ])."',
            },
            {
                src: 'https://images.unsplash.com/photo-1517384084767-6bc118943770?".http_build_query([
            'ixid' => 'MnwxfDB8MXxyYW5kb218MHx8dGV4dHVyZXx8fHx8fDE2ODE1OTE2OTI',
            'auto' => 'compress,enhance,format',
            'crop' => 'edges',
            'fit' => 'crop',
            'fm' => 'jpg',
            'w' => 1200,
            'h' => 900,
        ])."',
            },
            {
                src: 'https://images.unsplash.com/photo-1566305977571-5666677c6e98?".http_build_query([
            'ixid' => 'MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8',
            'auto' => 'compress,enhance,format',
            'crop' => 'edges',
            'fit' => 'crop',
            'fm' => 'jpg',
            'w' => 1200,
            'h' => 900,
        ])."',
            },
        ]",

        '{videoObj}' => "[
            {
                id: '7GgVIflnbgg',
                startAtMin: 0,
                startAtMax: 0,
                abundance: 0.08
            },
            {
                id: 'MLm07I49RiE',
                startAtMin: 15,
                startAtMax: 7200,
                abundance: 0.08
            },
        ]",
    ],
];
