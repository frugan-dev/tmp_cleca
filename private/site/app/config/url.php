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

return [
    'base' => $_ENV['HTTP_HOST'] ?? '/',

    // true adds the trailing slash (false removes it)
    'trailingSlash' => false,

    // e.g. .html
    // PHP built-in server doesn't rewrite
    'extension' => '',

    'www' => false,

    // Simple HTTPS configuration (boolean)
    'https' => false, // maybe 'false', if behind a proxy..

    // Advanced HTTPS configuration (array)
    // 'https' => [
    //     'enabled' => true, // maybe 'false', if behind a proxy..
    //     'redirect' => true,
    //     'maxAge' => 31536000, // One year in seconds
    //     'includeSubdomains' => false,
    //     'preload' => false,
    //     'checkHttpsForward' => false, // Check X-Forwarded-Proto headers
    // ],

    'webalize.regex' => null,

    'nette.webalize.charlist' => null,
];
