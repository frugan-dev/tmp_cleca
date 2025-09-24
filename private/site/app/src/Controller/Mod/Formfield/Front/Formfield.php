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

namespace App\Controller\Mod\Formfield\Front;

use App\Controller\Mod\Formfield\FormfieldTrait;
use App\Model\Mod\Front\Mod;

class Formfield extends Mod
{
    use \App\Controller\Mod\Formfield\FormfieldActionTrait;
    use \App\Controller\Mod\Formfield\FormfieldEventTrait;
    use FormfieldActionTrait;
    use FormfieldEventTrait;
    use FormfieldTrait;
}
