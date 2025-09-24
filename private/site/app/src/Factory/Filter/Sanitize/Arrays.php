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

class Arrays
{
    public function __invoke($subject, $field)
    {
        if (\is_array($subject->{$field})) {
            return true;
        }

        if (empty($subject->{$field})) {
            $subject->{$field} = [];

            return true;
        }

        return false;
    }
}
