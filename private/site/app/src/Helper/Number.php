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

namespace App\Helper;

class Number extends Helper
{
    // https://www.geeksforgeeks.org/php-sum-digits-number/
    public function sum(string $num, $max = false)
    {
        $sum = 0;

        for ($i = 0; $i < \strlen($num); ++$i) {
            $sum += $num[$i];
        }

        if ($sum > 9) {
            if ($sum > $max) {
                return $this->sum($sum);
            }
        }

        return $sum;
    }
}
