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

namespace App\Controller\Mod\Form\Api;

use App\Controller\Mod\Form\FormAlterAfterTrait;
use App\Controller\Mod\Form\FormAlterBeforeTrait;
use App\Controller\Mod\Form\FormEventTrait;
use App\Controller\Mod\Form\FormSanitizeTrait;
use App\Controller\Mod\Form\FormTrait;
use App\Controller\Mod\Form\FormValidateTrait;
use App\Model\Mod\Api\Mod;

class Form extends Mod
{
    use FormAlterAfterTrait;
    use FormAlterBeforeTrait;
    use FormEventTrait;
    use FormSanitizeTrait;
    use FormTrait;
    use FormValidateTrait;
}
