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

namespace App\Controller\Env\Api;

use App\Controller\Env\AuthAdapterTrait;
use App\Factory\Auth\Adapter\AuthAdapterInterface;
use App\Model\Model;

class AuthAdapter extends Model implements AuthAdapterInterface
{
    use AuthAdapterTrait;
    public static string $env = 'api';
}
