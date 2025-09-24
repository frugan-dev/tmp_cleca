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

namespace App\Controller\Mod\Catmember\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait CatmemberV1ActionPostTrait
{
    protected function _v1ActionPostAdd(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->_actionGlobal($request, $response, $args);

        parent::_v2ActionPostAdd($request, $response, $args);
    }
}
