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

// https://symfony.com/doc/current/reference/configuration/framework.html#session
return [
    'name' => 'default',

    'localStorage.installPopover.time' => (60 * 60 * 12 * 30),
];
