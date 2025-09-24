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

namespace App\Factory\Filter\Sanitize;

use Aura\Filter\Rule\Sanitize\Double;

// FIXME - https://github.com/auraphp/Aura.Filter/pull/139
class Floats extends Double
{
    #[\Override]
    public function __invoke($subject, $field)
    {
        $subject->{$field} = (float) $subject->{$field};

        return parent::__invoke($subject, $field);
    }
}
