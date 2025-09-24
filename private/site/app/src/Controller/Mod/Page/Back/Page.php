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

namespace App\Controller\Mod\Page\Back;

use App\Controller\Mod\Page\PageSanitizeTrait;
use App\Controller\Mod\Page\PageTrait;
use App\Model\Mod\Back\Mod;

class Page extends Mod
{
    use \App\Controller\Mod\Page\PageEventTrait;
    use PageEventTrait;
    use PageSanitizeTrait;
    use PageTrait;
}
