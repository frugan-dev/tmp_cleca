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

use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;

class AddScheme
{
    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
    ) {}

    public function __invoke($subject, $field)
    {
        $subject->{$field} = $this->helper->Url()->addScheme($subject->{$field});

        return true;
    }
}
