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

use RedBeanPHP\Facade as R;

// Create an extension to by-pass security check in R::dispense
// http://www.redbeanphp.com/index.php?p=/prefixes
R::ext(basename(__FILE__, '.php'), fn ($type) => R::getRedBean()->dispense($type));
