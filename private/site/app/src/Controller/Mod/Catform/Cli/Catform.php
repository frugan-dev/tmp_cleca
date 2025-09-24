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

namespace App\Controller\Mod\Catform\Cli;

use App\Controller\Mod\Catform\CatformAlterBeforeTrait;
use App\Controller\Mod\Catform\CatformEventTrait;
use App\Controller\Mod\Catform\CatformInterface;
use App\Controller\Mod\Catform\CatformSanitizeTrait;
use App\Controller\Mod\Catform\CatformTrait;
use App\Controller\Mod\Catform\CatformValidateTrait;
use App\Model\Mod\Cli\Mod;

class Catform extends Mod implements CatformInterface
{
    use CatformAlterBeforeTrait;
    use CatformEventTrait;
    use CatformSanitizeTrait;
    use CatformTrait;
    use CatformValidateTrait;
}
