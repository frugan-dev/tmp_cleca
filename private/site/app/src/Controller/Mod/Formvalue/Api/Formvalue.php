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

namespace App\Controller\Mod\Formvalue\Api;

use App\Controller\Mod\Formvalue\FormvalueAlterAfterTrait;
use App\Controller\Mod\Formvalue\FormvalueAlterBeforeTrait;
use App\Controller\Mod\Formvalue\FormvalueEventTrait;
use App\Controller\Mod\Formvalue\FormvalueSanitizeTrait;
use App\Controller\Mod\Formvalue\FormvalueTrait;
use App\Controller\Mod\Formvalue\FormvalueValidateTrait;
use App\Model\Mod\Api\Mod;

class Formvalue extends Mod
{
    use FormvalueAlterAfterTrait;
    use FormvalueAlterBeforeTrait;
    use FormvalueEventTrait;
    use FormvalueSanitizeTrait;
    use FormvalueTrait;
    use FormvalueV1ActionDeleteTrait;
    use FormvalueV1ActionDeleteXhrTrait;
    use FormvalueV1ActionPostTrait;
    use FormvalueV1ActionPostXhrTrait;
    use FormvalueValidateTrait;
}
