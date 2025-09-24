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

class StripTags
{
    public function __invoke($subject, $field, $allowable_tags = '')
    {
        $subject->{$field} = strip_tags((string) $subject->{$field}, $allowable_tags);

        return true;
    }
}
