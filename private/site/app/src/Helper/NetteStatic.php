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

class NetteStatic
{
    public function __construct(protected $namespace) {}

    public function __call($name, $args)
    {
        return \call_user_func_array(
            [$this->namespace, $name],
            $args
        );
    }
}
