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
    'version' => 1,

    'key.length' => 50,

    // https://github.com/nette/utils/blob/master/src/Utils/Random.php#L25
    'key.charlist' => '0-9a-zA-Z_|!$%&/()=?^[]{}*@#.:,;<>+-',

    'headers.key' => 'X-API-Key',

    'public.key' => $_ENV['API_PUBLIC_KEY'],

    'sse.enabled' => false,
];
