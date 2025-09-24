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
$htmlMinify = in_array(getClientIp(), $debugWhitelistIps, true) ? false : true;

return [
    'minify.enabled' => $htmlMinify,
    'minify.aggressive' => false,
    'minify.experimental' => false,

    'escaper.encoding' => 'UTF-8',
    'escaper.flags' => ENT_QUOTES | ENT_SUBSTITUTE,
];
