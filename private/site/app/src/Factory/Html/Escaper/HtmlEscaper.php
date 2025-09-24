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

class HtmlEscaper extends HelperLocatorFactory
{
    public function __invoke($raw)
    {
        if (!\is_string($raw)) {
            return $raw;
        }

        $return = strtr($raw, [
            // https://www.php.net/manual/en/function.html-entity-decode.php
            // You might wonder why trim(html_entity_decode('&nbsp;')); doesn't reduce the string to an empty string,
            // that's because the '&nbsp;' entity is not ASCII code 32 (which is stripped by trim())
            // but ASCII code 160 (0xa0) in the default ISO 8859-1 encoding.
            '&nbsp;' => ' ',
        ]);

        $return = html_entity_decode($return);

        return $this->escaper->html($return);
    }
}
