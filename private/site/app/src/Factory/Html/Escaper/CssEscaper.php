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

class CssEscaper extends HelperLocatorFactory
{
    public function __invoke($raw)
    {
        $return = null;
        if (!\is_string($raw)) {
            return $return;
        }

        $return = $this->escaper->css($raw);

        return strtr($return, [
            '\20 ' => ' ',
            '\23 ' => '#',
            '\25 ' => '%',
            '\28 ' => '(',
            '\29 ' => ')',
            '\2F ' => '/',
        ]);
    }
}
