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

namespace App\Model\Mod\Cli;

class Mod extends \App\Model\Mod\Mod
{
    public static string $env = 'cli';

    #[\Override]
    protected function _customCondition()
    {
        return true;
    }
}
