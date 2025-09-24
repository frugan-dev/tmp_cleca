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

namespace App\Factory\Html\Helper;

class Scripts extends \Aura\Html\Helper\Scripts
{
    #[\Override]
    protected function attr($src = null, array $attr = [])
    {
        if (null !== $src) {
            $attr['src'] = $src;
        }
        if (empty($attr['type'])) {
            $attr['type'] = 'text/javascript';
        }

        return $this->escaper->attr($attr);
    }
}
