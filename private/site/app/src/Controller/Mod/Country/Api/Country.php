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

namespace App\Controller\Mod\Country\Api;

use App\Controller\Mod\Country\CountryEventTrait;
use App\Controller\Mod\Country\CountrySanitizeTrait;
use App\Controller\Mod\Country\CountryTrait;
use App\Controller\Mod\Country\CountryValidateTrait;
use App\Model\Mod\Api\Mod;

class Country extends Mod
{
    use CountryEventTrait;
    use CountrySanitizeTrait;
    use CountryTrait;
    use CountryValidateTrait;
}
