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

// Docker
if ('/opt/bitnami/php/bin/php' === trim((string) shell_exec('which php'))) {
    $_SERVER['APP_ENV'] = 'develop';
}

// define('_APP_ENV', 'staging');
