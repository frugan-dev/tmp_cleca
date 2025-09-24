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

namespace App\Controller\Mod\Member\Api;

use App\Controller\Mod\Member\MemberEventTrait;
use App\Controller\Mod\Member\MemberSanitizeTrait;
use App\Controller\Mod\Member\MemberTrait;
use App\Controller\Mod\Member\MemberValidateTrait;
use App\Model\Mod\Api\Mod;

class Member extends Mod
{
    use MemberEventTrait;
    use MemberSanitizeTrait;
    use MemberTrait;
    use MemberValidateTrait;
}
