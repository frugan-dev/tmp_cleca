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

namespace App\Controller\Mod\Log\Api;

use App\Controller\Mod\Log\LogEventTrait;
use App\Controller\Mod\Log\LogTrait;
use App\Model\Mod\Api\Mod;

class Log extends Mod
{
    use LogEventTrait;
    use LogTrait;
}
