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

namespace App\Controller\Mod\Catuser\Api;

use App\Controller\Mod\Catuser\CatuserActionTrait;
use App\Controller\Mod\Catuser\CatuserEventTrait;
use App\Controller\Mod\Catuser\CatuserSanitizeTrait;
use App\Controller\Mod\Catuser\CatuserTrait;
use App\Model\Mod\Api\Mod;

class Catuser extends Mod
{
    use \App\Controller\Mod\Catuser\CatuserValidateTrait;
    use CatuserActionTrait;
    use CatuserEventTrait;
    use CatuserSanitizeTrait;
    use CatuserTrait;
    use CatuserV1ActionPostTrait;
    use CatuserV1ActionPutTrait;
    use CatuserValidateTrait;
}
