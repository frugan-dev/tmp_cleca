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

namespace App\Factory\Filter\Validate;

class Hex
{
    public function __invoke($subject, $field, $max = null)
    {
        // must be scalar
        $value = $subject->{$field};

        if (!\is_scalar($value)) {
            return false;
        }

        // must be hex
        $hex = ctype_xdigit($value);
        if (!$hex) {
            return false;
        }

        // must be no longer than $max chars
        if ($max && \strlen($value) > $max) {
            return false;
        }

        // done!
        return true;
    }
}
