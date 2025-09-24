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

namespace App\Factory\ArraySiblings;

// https://gist.github.com/gonzalo123/882954
interface ArraySiblingsInterface
{
    public static function previous($needle, $haystack, $continueAtTop = true);

    public static function next($needle, $haystack, $continueAtBottom = true);
}
