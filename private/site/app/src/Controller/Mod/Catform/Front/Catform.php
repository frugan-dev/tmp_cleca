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

namespace App\Controller\Mod\Catform\Front;

use App\Controller\Mod\Catform\CatformEventTrait;
use App\Controller\Mod\Catform\CatformInterface;
use App\Controller\Mod\Catform\CatformTrait;
use App\Model\Mod\Front\Mod;

class Catform extends Mod implements CatformInterface
{
    use CatformActionTrait;
    use CatformEventTrait;
    use CatformMiddlewareTrait;
    use CatformTrait;
}
