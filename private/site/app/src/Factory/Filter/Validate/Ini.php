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

class Ini
{
    public function __invoke($subject, $field)
    {
        $value = $subject->{$field};

        try {
            // https://php.watch/articles/parse_ini_string-file-security-considerations
            return @\Safe\parse_ini_string((string) $value, true, INI_SCANNER_RAW);
        } catch (\Exception) {
        }

        return false;
    }
}
