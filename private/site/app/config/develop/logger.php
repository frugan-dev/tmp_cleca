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

use Monolog\Level;

return [
    'handlers.file.level' => Level::Debug,

    // 'channels.internal.handlers.file.level' => Level::Debug,

    'error_handler.php_errors_levels.'.E_WARNING => 'debug',
    'error_handler.php_errors_levels.'.E_DEPRECATED => 'debug',
];
