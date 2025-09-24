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

namespace App\Controller\Mod\Catmember\Front;

use App\Controller\Mod\Catmember\CatmemberEventTrait;
use App\Controller\Mod\Catmember\CatmemberTrait;
use App\Model\Mod\Front\Mod;

class Catmember extends Mod
{
    use CatmemberEventTrait;
    use CatmemberTrait;
}
