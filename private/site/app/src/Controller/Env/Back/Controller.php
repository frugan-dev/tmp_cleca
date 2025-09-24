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

namespace App\Controller\Env\Back;

class Controller extends \App\Model\Controller
{
    use ActionTrait;

    public static string $env = 'back';

    public string $viewLayout = 'master';
}
