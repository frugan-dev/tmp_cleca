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

namespace App\Controller\Mod\Formvalue\Cli;

use App\Controller\Mod\Formvalue\FormvalueEventTrait;
use App\Controller\Mod\Formvalue\FormvalueSanitizeTrait;
use App\Controller\Mod\Formvalue\FormvalueTrait;
use App\Model\Mod\Cli\Mod;

class Formvalue extends Mod
{
    use FormvalueEventTrait;
    use FormvalueSanitizeTrait;
    use FormvalueTrait;
}
