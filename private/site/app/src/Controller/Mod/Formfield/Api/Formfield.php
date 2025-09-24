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

namespace App\Controller\Mod\Formfield\Api;

use App\Controller\Mod\Formfield\FormfieldAlterBeforeTrait;
use App\Controller\Mod\Formfield\FormfieldEventTrait;
use App\Controller\Mod\Formfield\FormfieldSanitizeTrait;
use App\Controller\Mod\Formfield\FormfieldTrait;
use App\Controller\Mod\Formfield\FormfieldValidateTrait;
use App\Model\Mod\Api\Mod;

class Formfield extends Mod
{
    use FormfieldAlterBeforeTrait;
    use FormfieldEventTrait;
    use FormfieldSanitizeTrait;
    use FormfieldTrait;
    use FormfieldV1ActionGetTrait;
    use FormfieldValidateTrait;
}
