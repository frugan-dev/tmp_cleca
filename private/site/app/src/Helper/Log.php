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

class Log extends Helper
{
    public function getCounter($name)
    {
        $counter = 0;

        $file = _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/logs/'.$name.'.log';

        if (file_exists($file)) {
            $counter = (int) trim(\Safe\file_get_contents($file));
        }

        ++$counter;

        \Safe\file_put_contents($file, $counter);

        return $counter;
    }
}
