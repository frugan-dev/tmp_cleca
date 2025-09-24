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

namespace App\Factory\Html\Escaper;

use Aura\Html\HelperLocatorFactory;

class AttrEscaper extends HelperLocatorFactory
{
    public function __invoke($raw)
    {
        $return = (\is_array($raw) && !empty($raw) ? ' ' : '').$this->escaper->attr($raw);

        if (!\is_string($return)) {
            return $return;
        }

        return strtr($return, [
            '&#x20;' => ' ',
            '&#x2F;' => '/',
            '&#x23;' => '#',
            '&#x27;' => '\'',
            '&#x3A;' => ':',
            '&#x3B;' => ';',
            '&#039;' => '\'',
            '&#x40;' => '@',
            '&#x7C;' => '|',
        ]);
    }
}
