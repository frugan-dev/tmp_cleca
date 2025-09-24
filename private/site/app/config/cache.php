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

// https://odan.github.io/2019/12/20/slim4-performance-testing.html
return [
    // https://github.com/slimphp/Slim-HttpCache
    'http.middleware.enabled' => false,
    'http.provider.enabled' => true,

    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
    // https://stackoverflow.com/a/51056834/3929620
    'http.type' => 'private',
    'http.maxAge' => 86400,
    'http.mustRevalidate' => true,

    'http.provider.denyCache' => true,
    'http.provider.allowCache' => false,
    'http.provider.expires' => false, // strtotime('+1 week'), // empty or a UNIX timestamp or a valid `strtotime()` string
    'http.provider.etag.enabled' => true,
    'http.provider.etag.type' => 'strong', // `strong` (default) or `weak`
    'http.provider.lastModified' => false, // strtotime('-1 week'), // empty or a UNIX timestamp or a valid `strtotime()` string

    // https://symfony.com/doc/current/components/html
    'storage.enabled' => true,
    'api.storage.enabled' => true,
    'back.storage.enabled' => false,
    'front.storage.enabled' => false,
    'js.storage.enabled' => true,
    'xml.storage.enabled' => true,

    // https://www.php.net/manual/en/datetime.formats.relative.php
    // By default, cache items are stored permanently
    // integer, DateInterval or null
    'storage.expire' => null,
    'cli.storage.expire' => 0,
    'api.storage.expire' => 300,
    'js.storage.expire' => 'tomorrow',
    'xml.storage.expire' => 'tomorrow',

    // filesystem (PSR-16), filesystemTagAware (PSR-6), phpFiles, pdo, memcached
    'storage.adapter' => 'filesystemTagAware',

    'storage.adapter.filesystem.path' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/cache'),
    'storage.adapter.filesystemTagAware.path' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/cache'),
    'storage.adapter.phpFiles.path' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/cache'),

    'storage.body.header' => 'Cache-Body-PSR-6',

    'translation.enabled' => true,
];
