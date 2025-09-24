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

namespace App\Helper;

use App\Factory\Translator\TranslatorInterface;
use Carbon\Carbon as CarbonStatic;

class Carbon extends Helper
{
    #[\Override]
    public function __call($name, $args)
    {
        // CarbonStatic::getAvailableLocales();
        CarbonStatic::setLocale($this->container->get(TranslatorInterface::class)->locale);

        return \call_user_func_array(
            [CarbonStatic::class, $name],
            $args
        );
    }
}
