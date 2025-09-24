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

namespace App\Controller\Mod\Setting\Cli;

use App\Controller\Mod\Setting\SettingAlterAfterTrait;
use App\Controller\Mod\Setting\SettingAlterBeforeTrait;
use App\Controller\Mod\Setting\SettingEventTrait;
use App\Controller\Mod\Setting\SettingSanitizeTrait;
use App\Controller\Mod\Setting\SettingTrait;
use App\Controller\Mod\Setting\SettingValidateTrait;
use App\Model\Mod\Cli\Mod;

class Setting extends Mod
{
    use SettingAlterAfterTrait;
    use SettingAlterBeforeTrait;
    use SettingEventTrait;
    use SettingSanitizeTrait;
    use SettingTrait;
    use SettingValidateTrait;
}
