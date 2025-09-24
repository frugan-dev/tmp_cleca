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

define('_ROOT', dirname(__DIR__).'/private/site');
define('_BOOT', __DIR__);
define('_PUBLIC', _BOOT);
// define('_ENV', 'staging');

(require _ROOT.'/bootstrap.php')->run();
