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
use App\Model\Model;

class ArraySiblingsFactory extends Model implements ArraySiblingsInterface
{
    final public const string FORWARD = 'fwd';

    final public const string BACKWARDS = 'bwd';

    #[\Override]
    public static function next($needle, $haystack, $continueAtBottom = true)
    {
        return self::_walk($needle, $haystack, $continueAtBottom, self::FORWARD);
    }

    #[\Override]
    public static function previous($needle, $haystack, $continueAtTop = true)
    {
        return self::_walk($needle, $haystack, $continueAtTop, self::BACKWARDS);
    }

    private static function _walk($needle, $haystack, $continue, $direction)
    {
        if (self::BACKWARDS === $direction) {
            $haystack = array_reverse($haystack, true);
        }
        $keys = array_keys($haystack);

        if ($continue) {
            $keys = [...$keys, ...$keys];
        }

        $pos = array_keys($keys, $needle, true);
        if (!isset($pos[0])) {
            return false;
        }
        $pos = $pos[0];

        $next = $pos + 1;
        if (!isset($keys[$next])) {
            return false;
        }

        return $haystack[$keys[$next]];
    }
}
