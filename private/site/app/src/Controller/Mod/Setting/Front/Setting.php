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

namespace App\Controller\Mod\Setting\Front;

use App\Controller\Mod\Setting\SettingMiddlewareTrait;
use App\Controller\Mod\Setting\SettingTrait;
use App\Model\Mod\Front\Mod;

class Setting extends Mod
{
    use SettingMiddlewareTrait;
    use SettingTrait;
}
