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

namespace App\Controller\Mod\User\Cli;

use App\Controller\Mod\User\UserEventTrait;
use App\Controller\Mod\User\UserSanitizeTrait;
use App\Controller\Mod\User\UserTrait;
use App\Controller\Mod\User\UserValidateTrait;
use App\Model\Mod\Cli\Mod;

class User extends Mod
{
    use UserEventTrait;
    use UserSanitizeTrait;
    use UserTrait;
    use UserValidateTrait;
}
