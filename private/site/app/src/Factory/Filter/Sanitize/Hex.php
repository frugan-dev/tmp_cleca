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

namespace App\Factory\Filter\Sanitize;

class Hex
{
    public function __invoke($subject, $field, $max = null)
    {
        $value = $subject->{$field};

        // must be scalar
        if (!\is_scalar($value)) {
            // sanitizing failed
            return false;
        }

        // strip out non-hex characters
        $value = \Safe\preg_replace('/[^0-9a-f]/i', '', $value);
        if ('' === $value) {
            // failed to sanitize to a hex value
            return false;
        }

        // now check length and chop if needed
        if ($max && \strlen($value) > $max) {
            $value = substr($value, 0, $max);
        }

        // retain the sanitized value, and done!
        $subject->{$field} = $value;

        return true;
    }
}
