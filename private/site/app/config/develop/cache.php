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
    'http.middleware.enabled' => false,
    'http.provider.enabled' => true,

    'http.provider.denyCache' => true,
    'http.provider.expires' => false,
    'http.provider.etag.enabled' => false,
    'http.provider.lastModified' => false,

    'storage.enabled' => true,
    'api.storage.enabled' => false,
    'back.storage.enabled' => false,
    'front.storage.enabled' => false,
    'js.storage.enabled' => false,
    'xml.storage.enabled' => false,

    'translation.enabled' => false,
];
