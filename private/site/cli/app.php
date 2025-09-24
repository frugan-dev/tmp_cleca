#!/usr/bin/env php -q
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

// https://stackoverflow.com/a/870457/3929620

$override = __DIR__.'/'.basename(__FILE__, '.php').'.override.php';
if (file_exists($override)) {
    require $override;
}

// Docker
if ('/opt/bitnami/php/bin/php' === trim((string) shell_exec('which php'))) {
    // FIXME - when docker swarm restarts the container, sometimes it loses $_SERVER['APP_ENV']
    $_SERVER['APP_ENV'] ??= 'production';
}

if (!defined('_ROOT')) {
    define('_ROOT', dirname(__DIR__));
}
if (!defined('_BOOT')) {
    define('_BOOT', __DIR__);
}
if (!defined('_PUBLIC')) {
    define('_PUBLIC', dirname(__DIR__, 3).'/public');
}

require _ROOT.'/cli/main.php';
